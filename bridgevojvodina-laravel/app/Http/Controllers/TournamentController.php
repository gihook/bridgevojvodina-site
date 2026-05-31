<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\BoardSet;
use App\Models\Board;
use App\Models\Player;
use App\Services\TournamentHydrationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;

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

    public function destroy(Tournament $tournament): RedirectResponse
    {
        Gate::authorize('delete', $tournament);
        $tournament->delete();

        return redirect()->route('tournaments.index')
            ->with('success', __('Tournament deleted successfully.'));
    }
}
