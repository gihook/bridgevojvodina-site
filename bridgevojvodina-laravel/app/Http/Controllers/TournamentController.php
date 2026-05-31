<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\BoardSet;
use App\Models\Board;
use App\Models\Player;
use App\DTOs\Tournament\RoundDTO;
use App\DTOs\Tournament\MatchDTO;
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
        protected TournamentHydrationService $hydrationService
    ) {}

    public function index(): View
    {
        $tournaments = Tournament::latest()->paginate(10);
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
            'description' => 'required|string|max:255',
            'details' => 'required|string',
            'is_completed' => 'boolean',
        ]);

        $validated['is_completed'] = $request->has('is_completed');

        $tournament = $request->user()->tournaments()->create($validated);

        return redirect()->route('tournaments.index')
            ->with('success', __('Tournament created successfully.'));
    }

    public function show(Tournament $tournament): View
    {
        $tournament->load(['boardSets' => function($q) {
            $q->withCount('boards');
        }]);
        return view('tournaments.show', compact('tournament'));
    }

    public function match(Tournament $tournament, string $roundId, string $homeTeamId): View
    {
        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $round = collect($results->rounds)->firstWhere('id', $roundId);
        if (!$round) {
            abort(404);
        }

        $match = collect($round->matches)->firstWhere('home_team_id', $homeTeamId);
        if (!$match) {
            abort(404);
        }

        $this->hydrationService->hydrateMatch($match);

        return view('tournaments.match', compact('tournament', 'round', 'match', 'results'));
    }

    public function board(Tournament $tournament, string $roundId, int $boardNumber): View
    {
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

    public function edit(Request $request, Tournament $tournament): View
    {
        Gate::authorize('update', $tournament);
        
        $boardSets = $tournament->boardSets()->get();

        return view('tournaments.edit', compact('tournament', 'boardSets'));
    }

    public function showBoardSet(Tournament $tournament, BoardSet $boardSet): View
    {
        Gate::authorize('update', $tournament);
        
        if ($boardSet->tournament_id !== $tournament->id) {
            abort(404);
        }

        $boardSet->load('boards');
        $boards = $boardSet->boards->sortBy('board_number');

        return view('tournaments.board-sets.show', compact('tournament', 'boardSet', 'boards'));
    }

    public function destroyBoardSet(Tournament $tournament, BoardSet $boardSet): RedirectResponse
    {
        Gate::authorize('update', $tournament);

        if ($boardSet->tournament_id !== $tournament->id) {
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

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
        Gate::authorize('update', $tournament);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'details' => 'required|string',
            'is_completed' => 'boolean',
        ]);

        $validated['is_completed'] = $request->has('is_completed');

        $tournament->update($validated);

        return redirect()->route('tournaments.index')
            ->with('success', __('Tournament updated successfully.'));
    }

    public function uploadBoardSet(Request $request, Tournament $tournament): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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
                'tournament_id' => $tournament->id,
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

    public function editTeam(Tournament $tournament, string $teamId): View
    {
        Gate::authorize('update', $tournament);

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

    public function addPlayerToTeam(Request $request, Tournament $tournament, string $teamId): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function removePlayerFromTeam(Tournament $tournament, string $teamId, int $playerId): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function setTeamCaptain(Tournament $tournament, string $teamId, int $playerId): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function updateRoundStatus(Request $request, Tournament $tournament, string $roundId): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function editTeamNumbers(Tournament $tournament): View
    {
        Gate::authorize('update', $tournament);

        $results = $tournament->team_results;
        if (!$results) {
            abort(404);
        }

        $teams = collect($results->teams)->sortBy(fn($t) => $t->number ?? 999999);

        return view('tournaments.teams.numbers', compact('tournament', 'teams'));
    }

    public function updateTeamNumbers(Request $request, Tournament $tournament): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function updateTeam(Request $request, Tournament $tournament, string $teamId): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function reorderRound(Request $request, Tournament $tournament, string $roundId): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function updateSettings(Request $request, Tournament $tournament): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function generateRounds(Request $request, Tournament $tournament): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function uploadRoundsCsv(Request $request, Tournament $tournament): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function editMatch(Tournament $tournament, string $roundId, string $homeTeamId): View
    {
        Gate::authorize('update', $tournament);

        $results = $tournament->team_results;
        if (!$results) abort(404);

        $round = collect($results->rounds)->firstWhere('id', $roundId);
        if (!$round) abort(404);

        if ($round->status !== 'inProgress') {
            return abort(403, __('Results can only be entered for rounds in progress.'));
        }

        $match = collect($round->matches)->firstWhere('home_team_id', $homeTeamId);
        if (!$match) abort(404);

        $homeTeam = collect($results->teams)->firstWhere('id', $match->home_team_id);
        $awayTeam = collect($results->teams)->firstWhere('id', $match->away_team_id);

        if (!$homeTeam || !$awayTeam) {
            abort(404, __('Cannot enter results for a bye match.'));
        }

        $homePlayers = Player::whereIn('id', $homeTeam->player_ids)->get();
        $awayPlayers = Player::whereIn('id', $awayTeam->player_ids)->get();

        return view('tournaments.matches.edit', compact('tournament', 'round', 'match', 'homeTeam', 'awayTeam', 'homePlayers', 'awayPlayers'));
    }

    public function updateMatch(Request $request, Tournament $tournament, string $roundId, string $homeTeamId): RedirectResponse
    {
        Gate::authorize('update', $tournament);

        $results = $tournament->team_results;
        if (!$results) abort(404);

        $roundIndex = collect($results->rounds)->search(fn($r) => $r->id === $roundId);
        if ($roundIndex === false) abort(404);

        if ($results->rounds[$roundIndex]->status !== 'inProgress') {
            return back()->withErrors(['error' => __('Results can only be entered for rounds in progress.')]);
        }

        $matchIndex = collect($results->rounds[$roundIndex]->matches)->search(fn($m) => $m->home_team_id === $homeTeamId);
        if ($matchIndex === false) abort(404);

        $request->validate([
            'home_imp' => 'required|integer',
            'away_imp' => 'required|integer',
            'home_vp' => 'required|numeric|min:0|max:20',
            'away_vp' => 'required|numeric|min:0|max:20',
            'open_n_id' => 'nullable|integer|exists:players,id',
            'open_s_id' => 'nullable|integer|exists:players,id',
            'open_e_id' => 'nullable|integer|exists:players,id',
            'open_w_id' => 'nullable|integer|exists:players,id',
            'closed_n_id' => 'nullable|integer|exists:players,id',
            'closed_s_id' => 'nullable|integer|exists:players,id',
            'closed_e_id' => 'nullable|integer|exists:players,id',
            'closed_w_id' => 'nullable|integer|exists:players,id',
        ]);

        $match = $results->rounds[$roundIndex]->matches[$matchIndex];
        $match->home_imp = (int) $request->home_imp;
        $match->away_imp = (int) $request->away_imp;
        $match->home_vp = (float) $request->home_vp;
        $match->away_vp = (float) $request->away_vp;
        
        $match->open_ns_ids = array_map('intval', array_values(array_filter([$request->open_n_id, $request->open_s_id])));
        $match->open_ew_ids = array_map('intval', array_values(array_filter([$request->open_e_id, $request->open_w_id])));
        $match->closed_ns_ids = array_map('intval', array_values(array_filter([$request->closed_n_id, $request->closed_s_id])));
        $match->closed_ew_ids = array_map('intval', array_values(array_filter([$request->closed_e_id, $request->closed_w_id])));

        $this->recalculateStandings($results);
        $tournament->team_results = $results;
        $tournament->save();

        return redirect()->route('tournaments.edit', $tournament)
            ->with('success', __('Match updated successfully.'));
    }

    public function destroyRound(Tournament $tournament, string $roundId): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function destroyIdleRounds(Tournament $tournament): RedirectResponse
    {
        Gate::authorize('update', $tournament);

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

    public function destroy(Tournament $tournament): RedirectResponse
    {
        Gate::authorize('delete', $tournament);
        $tournament->delete();

        return redirect()->route('tournaments.index')
            ->with('success', __('Tournament deleted successfully.'));
    }
}
