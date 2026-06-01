<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use App\Models\BoardSet;
use App\Models\Board;
use App\Models\Player;
use App\DTOs\Tournament\RoundDTO;
use App\DTOs\Tournament\MatchDTO;
use App\DTOs\Tournament\MatchBoardDTO;
use App\DTOs\Tournament\TeamDTO;
use App\DTOs\Tournament\TournamentResultsDTO;
use App\Services\TournamentHydrationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class TournamentController extends Controller
{
    public function __construct(
        protected TournamentHydrationService $hydrationService,
        protected \App\Services\VpCalculationService $vpService,
        protected \App\Services\BridgeScoringService $scoringService
    ) {}

    protected function resolveTournament(string $id)
    {
        $draft = TournamentConfiguration::find($id);
        $published = Tournament::find($id);

        if ($draft && auth()->check()) {
            if (auth()->user()->isAdmin() || auth()->id() === $draft->user_id) {
                return $draft;
            }
        }

        return $published ?? Tournament::findOrFail($id);
    }

    protected function authorizeTournament(\Illuminate\Database\Eloquent\Model $tournament)
    {
        if ($tournament instanceof Tournament) {
            Gate::authorize('update', $tournament);
        } else {
            if (!auth()->check()) {
                abort(401);
            }
            if (!auth()->user()->isAdmin() && auth()->id() !== $tournament->user_id) {
                abort(403);
            }
        }
    }

    public function index(): View
    {
        $published = Tournament::latest()->get();
        $publishedIds = $published->pluck('id')->toArray();
        $drafts = collect();
        
        if (auth()->check()) {
            $query = TournamentConfiguration::latest();
            if (!auth()->user()->isAdmin()) {
                $query->where('user_id', auth()->id());
            }
            // Exclude drafts that are already published
            $query->whereNotIn('id', $publishedIds);
            $drafts = $query->get();
        }
        
        $tournaments = $published->concat($drafts)->sortByDesc('created_at');
        
        return view('tournaments.index', compact('tournaments'));
    }

    public function create(): View
    {
        Gate::authorize('create', Tournament::class);
        return view('tournaments.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Tournament::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'details' => 'nullable|string',
        ]);

        $tournamentConfiguration = TournamentConfiguration::create([
            'id' => Str::uuid(),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'details' => $validated['details'],
            'user_id' => $request->user()->id,
            'team_results' => [
                'teams' => [],
                'rounds' => [],
                'bye_vp' => 12.0,
                'boards_per_round' => 16
            ],
        ]);

        return redirect()->route('tournaments.edit', $tournamentConfiguration->id)
            ->with('success', __('Tournament created successfully.'));
    }

    public function show(string $id): View|RedirectResponse
    {
        $tournament = $this->resolveTournament($id);

        $tournament->load(['boardSets' => function($q) {
            $q->withCount('boards');
        }]);
        return view('tournaments.show', compact('tournament'));
    }

    public function match(string $tournamentId, string $roundId, string $matchId): View
    {
        $tournament = $this->resolveTournament($tournamentId);
        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $round = collect($results->rounds)->firstWhere('id', $roundId);
        if (!$round) {
            abort(404);
        }

        $match = collect($round->matches)->firstWhere('id', $matchId)
            ?? collect($round->matches)->firstWhere('home_team_id', $matchId);
            
        if (!$match) {
            abort(404);
        }

        $this->hydrationService->hydrateMatch($match);

        return view('tournaments.match', compact('tournament', 'round', 'match', 'results'));
    }

    public function board(string $tournamentId, string $roundId, int $boardNumber): View
    {
        $tournament = $this->resolveTournament($tournamentId);
        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $round = collect($results->rounds)->firstWhere('id', $roundId);
        if (!$round) {
            abort(404);
        }

        $boardResults = [];
        foreach ($round->matches as $match) {
            $matchBoard = collect($match->boards)->firstWhere('board_number', $boardNumber);
            if ($matchBoard) {
                $boardResults[] = [
                    'match' => $match,
                    'board' => $matchBoard,
                    'home_team' => collect($results->teams)->firstWhere('id', $match->home_team_id),
                    'away_team' => collect($results->teams)->firstWhere('id', $match->away_team_id),
                ];
            }
        }

        $boardData = $this->hydrationService->getBoardData($round, $boardNumber);

        // Calculate all available board numbers in this round
        $allBoardNumbers = collect($round->matches)
            ->flatMap(fn($m) => collect($m->boards)->pluck('board_number'))
            ->unique()
            ->sort()
            ->values();

        $currentIndex = $allBoardNumbers->search($boardNumber);
        $prevBoard = $currentIndex > 0 ? $allBoardNumbers[$currentIndex - 1] : null;
        $nextBoard = $currentIndex !== false && $currentIndex < $allBoardNumbers->count() - 1 
            ? $allBoardNumbers[$currentIndex + 1] 
            : null;

        // Calculate Datum
        $nsScores = [];
        foreach ($boardResults as $res) {
            if ($res['board']->home_score !== null) $nsScores[] = $res['board']->home_score;
            if ($res['board']->away_score !== null) $nsScores[] = $res['board']->away_score;
        }
        
        $datum = null;
        if (count($nsScores) > 0) {
            // Simple average for now. In professional setups, extremes are often trimmed.
            $datum = array_sum($nsScores) / count($nsScores);
        }

        return view('tournaments.board', compact('tournament', 'round', 'boardNumber', 'boardResults', 'boardData', 'results', 'datum', 'prevBoard', 'nextBoard'));
    }

    public function edit(Request $request, string $id): View
    {
        $tournament = $this->resolveTournament($id);
        $this->authorizeTournament($tournament);

        if (!$tournament->team_results) {
            $tournament->team_results = new TournamentResultsDTO(
                teams: [],
                rounds: [],
                bye_vp: 12.0,
                boards_per_round: 16
            );
            $tournament->save();
        }

        $boardSets = $tournament->boardSets()->get();

        return view('tournaments.edit', compact('tournament', 'boardSets'));
    }

    public function publish(Request $request, string $id): RedirectResponse
    {
        $tournamentConfiguration = TournamentConfiguration::findOrFail($id);
        $this->authorizeTournament($tournamentConfiguration);
        
        $tournament = $tournamentConfiguration->publishToTournament();

        return redirect()->route('tournaments.show', $tournament)
            ->with('success', __('Tournament published successfully.'));
    }

    public function showBoardSet(string $tournamentId, BoardSet $boardSet): View
    {
        $tournament = $this->resolveTournament($tournamentId);

        if ($boardSet->tournament_id !== $tournament->id && $boardSet->tournament_configuration_id !== $tournament->id) {
            abort(404);
        }

        $boardSet->load('boards');
        $boards = $boardSet->boards->sortBy('board_number');

        $results = $tournament->team_results;
        $boardResults = [];

        if ($results) {
            // Find ALL rounds that use this board set (could be multiple if set is reused)
            $relevantRounds = collect($results->rounds)->where('board_set_id', $boardSet->id);

            if ($relevantRounds->isNotEmpty()) {
                // Pre-load all players for this tournament for faster lookup
                $playerIds = collect($results->teams)->flatMap->player_ids->unique();
                $players = Player::whereIn('id', $playerIds)->get()->keyBy('id');

                foreach ($relevantRounds as $round) {
                    foreach ($round->matches as $match) {
                        if (!$match->home_team_id || !$match->away_team_id || $match->home_team_id === 'bye' || $match->away_team_id === 'bye') {
                            continue;
                        }

                        $homeTeam = collect($results->teams)->firstWhere('id', $match->home_team_id);
                        $awayTeam = collect($results->teams)->firstWhere('id', $match->away_team_id);

                        // Helper to get player names (last names joined by hyphen)
                        $getNames = function($ids) use ($players) {
                            if (empty($ids)) return '-';
                            return collect($ids)->map(fn($id) => optional($players->get($id))->last_name)->filter()->implode(' - ');
                        };

                        $openNs = $getNames($match->open_ns_ids);
                        $openEw = $getNames($match->open_ew_ids);
                        $closedNs = $getNames($match->closed_ns_ids);
                        $closedEw = $getNames($match->closed_ew_ids);

                        foreach ($match->boards as $boardData) {
                            // Add Open Room result if it exists
                            if ($boardData->home_score !== null) {
                                $boardResults[$boardData->board_number][] = [
                                    'round_name' => $round->name,
                                    'home_team' => $homeTeam->name ?? 'Unknown',
                                    'away_team' => $awayTeam->name ?? 'Unknown',
                                    'room' => 'Open',
                                    'ns_names' => $openNs,
                                    'ew_names' => $openEw,
                                    'contract' => $boardData->home_contract,
                                    'score' => $boardData->home_score,
                                    'home_imp' => $boardData->home_imp,
                                    'away_imp' => $boardData->away_imp,
                                ];
                            }
                            
                            // Add Closed Room result if it exists
                            if ($boardData->away_score !== null) {
                                $boardResults[$boardData->board_number][] = [
                                    'round_name' => $round->name,
                                    'home_team' => $homeTeam->name ?? 'Unknown',
                                    'away_team' => $awayTeam->name ?? 'Unknown',
                                    'room' => 'Closed',
                                    'ns_names' => $closedNs,
                                    'ew_names' => $closedEw,
                                    'contract' => $boardData->away_contract,
                                    'score' => $boardData->away_score,
                                    'home_imp' => $boardData->home_imp,
                                    'away_imp' => $boardData->away_imp,
                                ];
                            }
                        }
                    }
                }
            }
        }

        return view('tournaments.board-sets.show', compact('tournament', 'boardSet', 'boards', 'boardResults'));
    }

    public function destroyBoardSet(string $tournamentId, BoardSet $boardSet): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        if ($boardSet->tournament_id !== $tournament->id && $boardSet->tournament_configuration_id !== $tournament->id) {
            abort(404);
        }

        DB::transaction(function () use ($tournament, $boardSet) {
            // Unlink from rounds
            $results = $tournament->team_results;
            if ($results) {
                foreach ($results->rounds as $round) {
                    if ($round->board_set_id == $boardSet->id) {
                        $round->board_set_id = null;
                    }
                }
                $tournament->team_results = $results;
                $tournament->save();
            }

            $boardSet->delete();
        });

        return redirect()->route('tournaments.edit', $tournament)
            ->with('success', __('Board set deleted successfully.'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $tournament = $this->resolveTournament($id);
        $this->authorizeTournament($tournament);

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'details' => 'nullable|string',
        ];
        
        if ($tournament instanceof Tournament) {
            $rules['is_completed'] = 'boolean';
        }

        $validated = $request->validate($rules);

        if ($tournament instanceof Tournament) {
            $validated['is_completed'] = $request->has('is_completed');
        }

        $tournament->update($validated);

        return redirect()->route($tournament instanceof TournamentConfiguration ? 'tournament-configurations.index' : 'tournaments.index')
            ->with('success', __('Tournament updated successfully.'));
    }

    public function uploadBoardSet(Request $request, string $tournamentId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $request->validate([
            'round_id' => 'required|string',
            'board_set_file' => 'required|file',
        ]);

        $results = $tournament->team_results;
        if (!$results) {
            return back()->withErrors(['round_id' => __('No results found for this tournament.')]);
        }

        $roundIndex = collect($results->rounds)->search(fn($r) => $r->id === $request->round_id);
        if ($roundIndex === false) {
            return back()->withErrors(['round_id' => __('Invalid round selected.')]);
        }

        $file = $request->file('board_set_file');
        $content = file_get_contents($file->getRealPath());

        $boardsData = [];
        $eventName = 'Imported Board Set';
        $currentBoard = null;

        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match('/^\[Event "(.+)"\]$/', $line, $matches)) {
                if ($eventName === 'Imported Board Set' && !empty($matches[1])) {
                    $eventName = $matches[1];
                }
            } elseif (preg_match('/^\[Board "(.+)"\]$/', $line, $matches)) {
                if ($currentBoard !== null && isset($currentBoard['deal'])) {
                    $boardsData[] = $currentBoard;
                }
                $currentBoard = ['board_number' => $matches[1]];
            } elseif (preg_match('/^\[Deal "(.+):(.+)"\]$/', $line, $matches)) {
                if ($currentBoard === null) $currentBoard = [];
                $currentBoard['dealer'] = $matches[1];
                $currentBoard['deal'] = $matches[2];
            }
        }
        if ($currentBoard !== null && isset($currentBoard['deal'])) {
            $boardsData[] = $currentBoard;
        }

        if (empty($boardsData)) {
            return back()->withErrors(['board_set_file' => __('Invalid PBN format or no boards found.')]);
        }

        $boardSetId = null;

        DB::transaction(function () use ($boardsData, $eventName, $tournament, $results, $roundIndex, &$boardSetId) {
            $boardSet = BoardSet::create([
                'tournament_id' => $tournament instanceof Tournament ? $tournament->id : null,
                'tournament_configuration_id' => $tournament instanceof TournamentConfiguration ? $tournament->id : null,
                'name' => $eventName,
            ]);
            $boardSetId = $boardSet->id;

            $seatOrder = ['N', 'E', 'S', 'W'];
            $fullSeats = ['N' => 'North', 'S' => 'South', 'E' => 'East', 'W' => 'West'];

            foreach ($boardsData as $bData) {
                $handsStr = explode(' ', $bData['deal']);
                $startIndex = array_search($bData['dealer'], $seatOrder);
                
                $mappedHands = ['North' => [], 'South' => [], 'East' => [], 'West' => []];
                
                foreach ($handsStr as $i => $hStr) {
                    if ($hStr === '-') continue;
                    $seat = $seatOrder[($startIndex + $i) % 4];
                    $suits = explode('.', $hStr);
                    $mappedHands[$fullSeats[$seat]] = [
                        'S' => $suits[0] ?? '',
                        'H' => $suits[1] ?? '',
                        'D' => $suits[2] ?? '',
                        'C' => $suits[3] ?? '',
                    ];
                }

                Board::create([
                    'board_set_id' => $boardSet->id,
                    'board_number' => (int) $bData['board_number'],
                    'vulnerability' => $this->hydrationService->calculateVulnerability((int) $bData['board_number']),
                    'cards_north' => $mappedHands['North'],
                    'cards_south' => $mappedHands['South'],
                    'cards_east' => $mappedHands['East'],
                    'cards_west' => $mappedHands['West'],
                ]);
            }

            // Update round
            $results->rounds[$roundIndex]->board_set_id = $boardSet->id;
            $tournament->team_results = $results;
            $tournament->save();
        });

        return redirect()->route('tournaments.edit', $tournament)
            ->with('success', __('Board set uploaded successfully.'));
    }

    public function editTeam(string $tournamentId, string $teamId): View
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $team = collect($results->teams)->firstWhere('id', $teamId);
        if (!$team) {
            abort(404);
        }

        $allTournamentPlayerIds = collect($results->teams)->flatMap(fn($t) => $t->player_ids)->unique()->toArray();
        $teamPlayerIds = $team->player_ids ?? [];
        
        $currentPlayers = Player::whereIn('id', $teamPlayerIds)->orderBy('last_name')->get();
        
        // Players not in any team of this tournament
        $availablePlayers = Player::whereNotIn('id', $allTournamentPlayerIds)->orderBy('last_name')->get();

        return view('tournaments.teams.edit', compact('tournament', 'team', 'currentPlayers', 'availablePlayers'));
    }

    public function addPlayerToTeam(Request $request, string $tournamentId, string $teamId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $request->validate([
            'player_id' => 'required|integer|exists:players,id',
        ]);

        $results = $tournament->team_results;
        if (!$results) abort(404);

        $allTournamentPlayerIds = collect($results->teams)->flatMap(fn($t) => $t->player_ids)->unique()->toArray();
        if (in_array($request->player_id, $allTournamentPlayerIds)) {
            return back()->withErrors(['player_id' => __('Player is already registered in a team for this tournament.')]);
        }

        $teamIndex = collect($results->rounds)->search(fn($t) => false); // Just finding index
        $teamIndex = collect($results->teams)->search(fn($t) => $t->id === $teamId);
        if ($teamIndex === false) abort(404);

        $playerIds = $results->teams[$teamIndex]->player_ids ?? [];
        $playerIds[] = (int) $request->player_id;
        $results->teams[$teamIndex]->player_ids = array_values(array_unique($playerIds));

        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Player added to team.'));
    }

    public function removePlayerFromTeam(string $tournamentId, string $teamId, int $playerId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $results = $tournament->team_results;
        if (!$results) abort(404);

        $teamIndex = collect($results->teams)->search(fn($t) => $t->id === $teamId);
        if ($teamIndex === false) abort(404);

        $playerIds = $results->teams[$teamIndex]->player_ids ?? [];
        $results->teams[$teamIndex]->player_ids = array_values(array_filter($playerIds, fn($id) => $id != $playerId));

        // If removed player was captain, unset captain
        if ($results->teams[$teamIndex]->captain_id == $playerId) {
            $results->teams[$teamIndex]->captain_id = 0;
        }

        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Player removed from team.'));
    }

    public function setTeamCaptain(string $tournamentId, string $teamId, int $playerId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $results = $tournament->team_results;
        if (!$results) abort(404);

        $teamIndex = collect($results->teams)->search(fn($t) => $t->id === $teamId);
        if ($teamIndex === false) abort(404);

        if (!in_array($playerId, $results->teams[$teamIndex]->player_ids)) {
            return back()->withErrors(['error' => __('Player must be in the team to be captain.')]);
        }

        $results->teams[$teamIndex]->captain_id = $playerId;

        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Team captain updated.'));
    }

    public function updateRoundStatus(Request $request, string $tournamentId, string $roundId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $request->validate([
            'status' => 'required|string|in:idle,inProgress,complete',
        ]);

        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $roundIndex = collect($results->rounds)->search(fn($r) => $r->id === $roundId);
        if ($roundIndex === false) {
            abort(404);
        }

        $results->rounds[$roundIndex]->status = $request->status;

        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Round status updated.'));
    }

    public function editTeamNumbers(string $tournamentId): View
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $teams = collect($results->teams)->sortBy(fn($t) => $t->number ?? 999999);

        return view('tournaments.teams.numbers', compact('tournament', 'teams'));
    }

    public function updateTeamNumbers(Request $request, string $tournamentId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $teamCount = count($results->teams);

        $request->validate([
            'numbers' => 'required|array',
            'numbers.*' => "nullable|integer|min:1|max:{$teamCount}",
        ]);

        $newNumbers = $request->input('numbers');
        $filteredNumbers = array_filter($newNumbers, fn($v) => !is_null($v) && $v !== '');
        
        if (count($filteredNumbers) !== count(array_unique($filteredNumbers))) {
            return back()->withErrors(['error' => __('Team numbers must be unique.')]);
        }

        foreach ($results->teams as $team) {
            if (isset($newNumbers[$team->id])) {
                $team->number = $newNumbers[$team->id] !== '' ? (int) $newNumbers[$team->id] : null;
            }
        }

        $tournament->team_results = $results;
        $tournament->save();

        return redirect()->route('tournaments.edit', $tournament)
            ->with('success', __('Team numbers updated successfully.'));
    }

    public function updateTeam(Request $request, string $tournamentId, string $teamId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $teamIndex = collect($results->teams)->search(fn($t) => $t->id === $teamId);
        if ($teamIndex === false) {
            abort(404);
        }

        $results->teams[$teamIndex]->name = $request->name;

        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Team name updated successfully.'));
    }

    public function reorderRound(Request $request, string $tournamentId, string $roundId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $request->validate([
            'direction' => 'required|string|in:up,down',
        ]);

        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $rounds = $results->rounds;
        $index = collect($rounds)->search(fn($r) => $r->id === $roundId);

        if ($index === false) {
            abort(404);
        }

        if ($rounds[$index]->status !== 'idle') {
            return back()->withErrors(['error' => __('Only idle rounds can be reordered.')]);
        }

        $direction = $request->direction;
        $newIndex = ($direction === 'up') ? $index - 1 : $index + 1;

        if ($newIndex < 0 || $newIndex >= count($rounds)) {
            return back();
        }

        // Swap
        $temp = $rounds[$index];
        $rounds[$index] = $rounds[$newIndex];
        $rounds[$newIndex] = $temp;

        $results->rounds = $rounds;
        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Round reordered successfully.'));
    }

    public function updateSettings(Request $request, string $tournamentId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $request->validate([
            'bye_vp' => 'required|numeric|min:0|max:20',
            'boards_per_round' => 'required|integer|min:1|max:64',
        ]);

        $results = $tournament->team_results;
        if (!$results) abort(404);

        $results->bye_vp = (float) $request->bye_vp;
        $results->boards_per_round = (int) $request->boards_per_round;
        
        $this->recalculateStandings($results);
        
        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Settings updated and standings recalculated.'));
    }

    protected function recalculateStandings(\App\DTOs\Tournament\TournamentResultsDTO $results): void
    {
        // Reset all team VPs
        foreach ($results->teams as $team) {
            $team->total_vp = 0;
        }

        // Process all matches in all rounds
        foreach ($results->rounds as $round) {
            $boards = $round->boards_per_round ?? $results->boards_per_round ?? 16;
            
            foreach ($round->matches as $match) {
                // If it's a bye, update the VP based on current tournament setting
                if (!$match->home_team_id || !$match->away_team_id) {
                    if ($match->home_team_id) {
                        $match->home_vp = $results->bye_vp;
                        $match->away_vp = 0;
                    } else {
                        $match->away_vp = $results->bye_vp;
                        $match->home_vp = 0;
                    }
                } else {
                    // Automatically calculate VP based on IMPs
                    if ($match->home_imp !== 0 || $match->away_imp !== 0) {
                        list($hVp, $aVp) = $this->vpService->calculateVp($match->home_imp, $match->away_imp, $boards);
                        $match->home_vp = $hVp;
                        $match->away_vp = $aVp;
                    }
                }

                // Add VPs to teams
                if ($match->home_team_id) {
                    $team = collect($results->teams)->firstWhere('id', $match->home_team_id);
                    if ($team) $team->total_vp += $match->home_vp;
                }
                if ($match->away_team_id) {
                    $team = collect($results->teams)->firstWhere('id', $match->away_team_id);
                    if ($team) $team->total_vp += $match->away_vp;
                }
            }
        }
    }

    public function generateRounds(Request $request, string $tournamentId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $request->validate([
            'format' => 'required|string|in:single_round_robin,double_round_robin',
            'boards_per_round' => 'required|integer|min:1|max:64',
        ]);

        $results = $tournament->team_results;
        if (!$results || count($results->teams) < 2) {
            return back()->withErrors(['format' => __('At least 2 teams are required to generate rounds.')]);
        }

        // Sort teams by number, fallback to index
        $teams = collect($results->teams)->sortBy(fn($t) => $t->number ?? 999999)->values()->toArray();
        $n = count($teams);
        $isOdd = $n % 2 !== 0;
        
        if ($isOdd) {
            $n++;
            // Dummy "bye" team with id null
            $teams[] = (object) ['id' => null, 'name' => __('bye')];
        }

        $rounds = [];
        $numRounds = $n - 1;
        $existingRoundCount = count($results->rounds);

        for ($r = 1; $r <= $numRounds; $r++) {
            $matches = [];
            
            // Fixed team is at index n-1
            $fixedTeam = $teams[$n - 1];
            $rotatingTeamIdx = ($r - 1) % ($n - 1);
            $opponent = $teams[$rotatingTeamIdx];

            if ($fixedTeam->id || $opponent->id) {
                // Alternating home/away for the fixed team
                // Fixed team is Home in odd rounds, Away in even rounds
                $isFixedHome = ($r % 2 !== 0);
                $homeId = $isFixedHome ? $fixedTeam->id : $opponent->id;
                $awayId = $isFixedHome ? $opponent->id : $fixedTeam->id;
                
                $matches[] = new MatchDTO(
                    id: Str::uuid()->toString(),
                    home_team_id: $homeId,
                    away_team_id: $awayId,
                    home_imp: 0, away_imp: 0, 
                    home_vp: (!$awayId ? $results->bye_vp : 0), 
                    away_vp: (!$homeId ? $results->bye_vp : 0)
                );
            }

            // Other pairings
            for ($k = 1; $k < $n / 2; $k++) {
                $idx1 = ($r - 1 - $k + ($n - 1)) % ($n - 1);
                $idx2 = ($r - 1 + $k) % ($n - 1);
                
                $t1 = $teams[$idx1];
                $t2 = $teams[$idx2];

                if ($t1->id || $t2->id) {
                    $matches[] = new MatchDTO(
                        id: Str::uuid()->toString(),
                        home_team_id: $t1->id,
                        away_team_id: $t2->id,
                        home_imp: 0, away_imp: 0, 
                        home_vp: (!$t2->id ? $results->bye_vp : 0), 
                        away_vp: (!$t1->id ? $results->bye_vp : 0)
                    );
                }
            }

            $rounds[] = new RoundDTO(
                id: Str::uuid()->toString(),
                name: __('Round') . ' ' . ($existingRoundCount + $r),
                status: 'idle',
                matches: $matches,
                boards_per_round: (int) $request->boards_per_round
            );
        }

        if ($request->format === 'double_round_robin') {
            $secondHalf = [];
            foreach ($rounds as $round) {
                $newMatches = [];
                foreach ($round->matches as $match) {
                    $newMatches[] = new MatchDTO(
                        id: Str::uuid()->toString(),
                        home_team_id: $match->away_team_id,
                        away_team_id: $match->home_team_id,
                        home_imp: 0, away_imp: 0, home_vp: 0, away_vp: 0
                    );
                }
                $secondHalf[] = new RoundDTO(
                    id: Str::uuid()->toString(),
                    name: __('Round') . ' ' . ($existingRoundCount + count($rounds) + count($secondHalf) + 1),
                    status: 'idle',
                    matches: $newMatches,
                    boards_per_round: (int) $request->boards_per_round
                );
            }
            $rounds = array_merge($rounds, $secondHalf);
        }

        $results->rounds = array_merge($results->rounds, $rounds);
        $this->recalculateStandings($results);
        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Rounds generated successfully.'));
    }

    public function uploadRoundsCsv(Request $request, string $tournamentId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $request->validate([
            'csv_file' => 'required|file',
            'boards_per_round' => 'required|integer|min:1|max:64',
        ]);

        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip header
        fgetcsv($handle);

        $roundsData = [];
        $teamMap = collect($results->teams)->keyBy('number');

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue;

            $roundName = trim($row[0]);
            $homeNum = trim($row[1]);
            $awayNum = trim($row[2]);

            if (empty($roundName) || empty($homeNum) || empty($awayNum)) continue;
            if (strtolower($homeNum) === 'bye' || strtolower($awayNum) === 'bye') continue;

            $homeTeam = $teamMap->get($homeNum);
            $awayTeam = $teamMap->get($awayNum);

            // One can be null (bye), but not both
            if (!$homeTeam && !$awayTeam) continue;

            if (!isset($roundsData[$roundName])) {
                $roundsData[$roundName] = [];
            }

            $roundsData[$roundName][] = new MatchDTO(
                id: Str::uuid()->toString(),
                home_team_id: $homeTeam?->id,
                away_team_id: $awayTeam?->id,
                home_imp: 0, away_imp: 0, 
                home_vp: (!$awayTeam ? $results->bye_vp : 0), 
                away_vp: (!$homeTeam ? $results->bye_vp : 0)
            );
        }
        fclose($handle);

        if (empty($roundsData)) {
            return back()->withErrors(['csv_file' => __('No valid matches found in CSV.')]);
        }

        $newRounds = [];
        foreach ($roundsData as $name => $matches) {
            $newRounds[] = new RoundDTO(
                id: Str::uuid()->toString(),
                name: $name,
                status: 'idle',
                matches: $matches,
                boards_per_round: (int) $request->boards_per_round
            );
        }

        $results->rounds = array_merge($results->rounds, $newRounds);
        $this->recalculateStandings($results);
        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Rounds uploaded successfully.'));
    }

    public function editMatchRoom(string $id, string $roundId, string $matchId, string $room): View
    {
        $tournament = $this->resolveTournament($id);
        $this->authorizeTournament($tournament);

        $results = $tournament->team_results;
        if (!$results) abort(404);

        $round = collect($results->rounds)->firstWhere('id', $roundId);
        if (!$round) abort(404);

        if ($round->status !== 'inProgress') {
            return abort(403, __('Results can only be entered for rounds in progress.'));
        }

        $match = collect($round->matches)->firstWhere('id', $matchId)
            ?? collect($round->matches)->firstWhere('home_team_id', $matchId);
            
        if (!$match) abort(404);

        $homeTeam = collect($results->teams)->firstWhere('id', $match->home_team_id);
        $awayTeam = collect($results->teams)->firstWhere('id', $match->away_team_id);

        if (!$homeTeam || !$awayTeam) {
            abort(404, __('Cannot enter results for a bye match.'));
        }

        $numBoards = $round->boards_per_round ?? $results->boards_per_round ?? 16;
        if (empty($match->boards)) {
            $boards = [];
            for ($i = 1; $i <= $numBoards; $i++) {
                $boards[] = new MatchBoardDTO(board_number: $i);
            }
            $match->boards = $boards;
        }

        // Determine which teams are NS and EW for this room
        // Open Room: NS = Home, EW = Away
        // Closed Room: NS = Away, EW = Home
        $nsTeam = $room === 'open' ? $homeTeam : $awayTeam;
        $ewTeam = $room === 'open' ? $awayTeam : $homeTeam;

        $nsPlayers = Player::whereIn('id', $nsTeam->player_ids)->get();
        $ewPlayers = Player::whereIn('id', $ewTeam->player_ids)->get();

        return view('tournaments.matches.room_edit', compact(
            'tournament', 'round', 'match', 'room',
            'homeTeam', 'awayTeam', 'nsTeam', 'ewTeam',
            'nsPlayers', 'ewPlayers', 'results'
        ));
    }

    public function updateMatchLineup(Request $request, string $id, string $roundId, string $matchId, string $room): RedirectResponse|array
    {
        $tournament = $this->resolveTournament($id);
        $this->authorizeTournament($tournament);

        $results = $tournament->team_results;
        if (!$results) abort(404);

        $roundIndex = collect($results->rounds)->search(fn($r) => $r->id === $roundId);
        if ($roundIndex === false) abort(404);
        
        $matchIndex = collect($results->rounds[$roundIndex]->matches)->search(fn($m) => ($m->id === $matchId || $m->home_team_id === $matchId));
        if ($matchIndex === false) abort(404);

        $request->validate([
            'n_id' => 'nullable|integer|exists:players,id',
            's_id' => 'nullable|integer|exists:players,id',
            'e_id' => 'nullable|integer|exists:players,id',
            'w_id' => 'nullable|integer|exists:players,id',
        ]);

        $match = $results->rounds[$roundIndex]->matches[$matchIndex];
        
        if ($room === 'open') {
            $match->open_ns_ids = array_map('intval', array_values(array_filter([$request->n_id, $request->s_id])));
            $match->open_ew_ids = array_map('intval', array_values(array_filter([$request->e_id, $request->w_id])));
        } else {
            $match->closed_ns_ids = array_map('intval', array_values(array_filter([$request->n_id, $request->s_id])));
            $match->closed_ew_ids = array_map('intval', array_values(array_filter([$request->e_id, $request->w_id])));
        }

        $tournament->team_results = $results;
        $tournament->save();

        if ($request->wantsJson()) {
            return ['success' => true];
        }
        return back()->with('success', __('Lineup updated.'));
    }

    public function updateMatchBoard(Request $request, string $id, string $roundId, string $matchId, string $room, int $boardNumber): RedirectResponse|array
    {
        $tournament = $this->resolveTournament($id);
        $this->authorizeTournament($tournament);

        $results = $tournament->team_results;
        if (!$results) abort(404);

        $roundIndex = collect($results->rounds)->search(fn($r) => $r->id === $roundId);
        if ($roundIndex === false) abort(404);
        $round = $results->rounds[$roundIndex];
        
        $matchIndex = collect($round->matches)->search(fn($m) => ($m->id === $matchId || $m->home_team_id === $matchId));
        if ($matchIndex === false) abort(404);

        $match = $round->matches[$matchIndex];

        // Ensure boards are initialized
        if (empty($match->boards)) {
            $numBoards = $round->boards_per_round ?? $results->boards_per_round ?? 16;
            $boards = [];
            for ($i = 1; $i <= $numBoards; $i++) {
                $boards[] = new MatchBoardDTO(board_number: $i);
            }
            $match->boards = $boards;
        }

        $boardIndex = collect($match->boards)->search(fn($b) => $b->board_number === $boardNumber);
        if ($boardIndex === false) abort(404);

        $data = $request->all();
        $isVul = $this->hydrationService->calculateVulnerability($boardNumber);
        
        // Contract Components
        $level = (int) ($data['contract_level'] ?? 0);
        if ($level === 0) {
            $score = 0;
            $contract = 'Pass';
            $decl = null;
            $tricks = null;
        } else {
            $suit = $data['contract_suit'];
            $risk = (int) $data['contract_risk'];
            $tricks = (int) $data['tricks'];
            $decl = $data['declarer'];

            $isRoomVul = ($isVul === 'All' || $isVul === ($decl === 'N' || $decl === 'S' ? 'NS' : 'EW'));
            $absScore = $this->scoringService->calculateScore($level, $suit, $risk, $tricks, $isRoomVul);
            $score = ($decl === 'N' || $decl === 'S') ? $absScore : -$absScore;
            
            $suffix = $risk === 2 ? 'X' : ($risk === 4 ? 'XX' : '');
            $contract = $level . $suit . $suffix;
        }

        $board = $match->boards[$boardIndex];
        if ($room === 'open') {
            $board->home_score = $score;
            $board->home_contract = $contract;
            $board->home_declarer = $decl;
            $board->home_tricks = $tricks;
        } else {
            $board->away_score = $score;
            $board->away_contract = $contract;
            $board->away_declarer = $decl;
            $board->away_tricks = $tricks;
        }

        // Recalculate board IMPs
        if ($board->home_score !== null && $board->away_score !== null) {
            $diff = $board->home_score - $board->away_score;
            $imp = $this->hydrationService->scoreToImp($diff);
            $board->home_imp = $imp > 0 ? $imp : 0;
            $board->away_imp = $imp < 0 ? abs($imp) : 0;
        } else {
            $board->home_imp = 0;
            $board->away_imp = 0;
        }

        // Recalculate match totals
        $totalHomeImp = 0;
        $totalAwayImp = 0;
        foreach ($match->boards as $b) {
            $totalHomeImp += $b->home_imp;
            $totalAwayImp += $b->away_imp;
        }
        $match->home_imp = $totalHomeImp;
        $match->away_imp = $totalAwayImp;

        $boardsCount = $round->boards_per_round ?? $results->boards_per_round ?? 16;
        list($hVp, $aVp) = $this->vpService->calculateVp($totalHomeImp, $totalAwayImp, $boardsCount);
        $match->home_vp = $hVp;
        $match->away_vp = $aVp;

        $this->recalculateStandings($results);
        $tournament->team_results = $results;
        $tournament->save();

        if ($request->wantsJson()) {
            return [
                'success' => true,
                'board' => $board->toArray(),
                'match_home_imp' => $match->home_imp,
                'match_away_imp' => $match->away_imp,
                'match_home_vp' => $match->home_vp,
                'match_away_vp' => $match->away_vp
            ];
        }

        return back()->with('success', __('Board updated.'));
    }

    public function destroyRound(string $tournamentId, string $roundId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $roundIndex = collect($results->rounds)->search(fn($r) => $r->id === $roundId);
        if ($roundIndex === false) {
            abort(404);
        }

        if ($results->rounds[$roundIndex]->status !== 'idle') {
            return back()->withErrors(['error' => __('Only idle rounds can be deleted.')]);
        }

        $newRounds = array_values(array_filter($results->rounds, fn($r) => $r->id !== $roundId));
        $results->rounds = $newRounds;
        
        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Round deleted successfully.'));
    }

    public function destroyIdleRounds(string $tournamentId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $newRounds = array_values(array_filter($results->rounds, fn($r) => $r->status !== 'idle'));
        
        if (count($newRounds) === count($results->rounds)) {
            return back()->with('info', __('No idle rounds found to delete.'));
        }

        $results->rounds = $newRounds;
        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('All idle rounds deleted successfully.'));
    }

    public function addTeam(Request $request, string $tournamentId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $results = $tournament->team_results;
        if (!$results) abort(404);

        $teamCount = count($results->teams);
        $newTeam = new TeamDTO(
            id: (string) Str::uuid(),
            name: $request->input('name'),
            captain_id: 0,
            player_ids: [],
            total_vp: 0,
            number: $teamCount + 1
        );

        $results->teams[] = $newTeam;
        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Team added successfully.'));
    }

    public function destroyTeam(string $tournamentId, string $teamId): RedirectResponse
    {
        $tournament = $this->resolveTournament($tournamentId);
        $this->authorizeTournament($tournament);

        $results = $tournament->team_results;
        if (!$results) abort(404);

        // Check if team is in any match
        foreach ($results->rounds as $round) {
            foreach ($round->matches as $match) {
                if ($match->home_team_id === $teamId || $match->away_team_id === $teamId) {
                    return back()->withErrors(['error' => __('Cannot delete team that is already in a schedule. Delete rounds first.')]);
                }
            }
        }

        $results->teams = array_values(array_filter($results->teams, fn($t) => $t->id !== $teamId));
        
        // Re-number teams
        foreach ($results->teams as $index => $team) {
            $team->number = $index + 1;
        }

        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('Team deleted successfully.'));
    }

    public function destroy(string $id): RedirectResponse
    {
        $tournament = $this->resolveTournament($id);
        
        if ($tournament instanceof Tournament) {
            Gate::authorize('delete', $tournament);
        }
        
        $tournament->delete();

        return redirect()->route('tournaments.index')
            ->with('success', __('Tournament deleted successfully.'));
    }
}
