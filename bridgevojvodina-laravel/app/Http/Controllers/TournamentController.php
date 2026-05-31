<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\BoardSet;
use App\Models\Board;
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

    public function edit(Tournament $tournament): View
    {
        Gate::authorize('update', $tournament);
        return view('tournaments.edit', compact('tournament'));
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
            'board_set_json' => 'required|file',
        ]);

        $results = $tournament->team_results;
        if (!$results) {
            return back()->withErrors(['round_id' => __('No results found for this tournament.')]);
        }

        $roundIndex = collect($results->rounds)->search(fn($r) => $r->id === $request->round_id);
        if ($roundIndex === false) {
            return back()->withErrors(['round_id' => __('Invalid round selected.')]);
        }

        $file = $request->file('board_set_json');
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);

        if (!$data || !isset($data['Name']) || !isset($data['Boards'])) {
            return back()->withErrors(['board_set_json' => __('Invalid JSON format.')]);
        }

        DB::transaction(function () use ($data, $tournament, $results, $roundIndex) {
            $boardSet = BoardSet::create([
                'tournament_id' => $tournament->id,
                'name' => $data['Name'],
            ]);

            $suitMap = ['Spades' => 'S', 'Hearts' => 'H', 'Diamonds' => 'D', 'Clubs' => 'C'];
            $rankMap = [
                'Ace' => 'A', 'King' => 'K', 'Queen' => 'Q', 'Jack' => 'J', 'Ten' => 'T',
                'Nine' => '9', 'Eight' => '8', 'Seven' => '7', 'Six' => '6', 'Five' => '5',
                'Four' => '4', 'Three' => '3', 'Two' => '2'
            ];

            foreach ($data['Boards'] as $boardData) {
                $hands = ['North' => [], 'South' => [], 'East' => [], 'West' => []];
                
                foreach ($boardData['Hands'] as $handData) {
                    $seat = $handData['Seat'];
                    $handArray = ['S' => '', 'H' => '', 'D' => '', 'C' => ''];
                    
                    foreach ($handData['Cards'] as $card) {
                        $suit = $suitMap[$card['Suit']] ?? null;
                        $rank = $rankMap[$card['Rank']] ?? null;
                        if ($suit && $rank) {
                            $handArray[$suit] .= $rank;
                        }
                    }
                    
                    if (isset($hands[$seat])) {
                        $hands[$seat] = $handArray;
                    }
                }

                Board::create([
                    'board_set_id' => $boardSet->id,
                    'board_number' => $boardData['BoardNumber'],
                    'vulnerability' => $this->hydrationService->calculateVulnerability($boardData['BoardNumber']),
                    'cards_north' => $hands['North'],
                    'cards_south' => $hands['South'],
                    'cards_east' => $hands['East'],
                    'cards_west' => $hands['West'],
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

    public function destroy(Tournament $tournament): RedirectResponse
    {
        Gate::authorize('delete', $tournament);
        $tournament->delete();

        return redirect()->route('tournaments.index')
            ->with('success', __('Tournament deleted successfully.'));
    }
}
