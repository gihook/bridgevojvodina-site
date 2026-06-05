<?php

namespace App\Http\Controllers;

use App\Models\Tournament;
use App\Models\Player;
use App\DTOs\Tournament\MatchBoardDTO;
use App\Services\BridgeScoringService;
use App\Services\TournamentHydrationService;
use App\Services\VpCalculationService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class PlayerScoringController extends Controller
{
    public function __construct(
        protected BridgeScoringService $scoringService,
        protected TournamentHydrationService $hydrationService,
        protected VpCalculationService $vpService
    ) {}

    /**
     * Helper to check if the current user has any active matches to score.
     */
    public static function hasActiveMatches(): bool
    {
        $user = Auth::user();
        if (!$user || !$user->player_id) return false;

        $activeTournaments = Tournament::where('is_completed', false)->get();
        foreach ($activeTournaments as $tournament) {
            $results = $tournament->team_results;
            if (!$results) continue;

            foreach ($results->rounds as $round) {
                if ($round->status === 'complete') continue;
                foreach ($round->matches as $match) {
                    if (in_array($user->player_id, $match->open_ns_ids) || 
                        in_array($user->player_id, $match->open_ew_ids) ||
                        in_array($user->player_id, $match->closed_ns_ids) || 
                        in_array($user->player_id, $match->closed_ew_ids)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Display a list of active tournaments/matches for the logged-in player.
     */
    public function index(): View
    {
        $user = Auth::user();
        if (!$user->player_id) {
            return view('scoring.index', ['matches' => [], 'error' => __('You must be linked to a player to enter scores.')]);
        }

        $activeTournaments = Tournament::where('is_completed', false)->get();
        $myMatches = [];

        foreach ($activeTournaments as $tournament) {
            $results = $tournament->team_results;
            if (!$results) continue;

            foreach ($results->rounds as $round) {
                // We only show matches for rounds that are 'in_progress' or 'idle'
                // Usually players score during in_progress.
                if ($round->status === 'complete') continue;

                foreach ($round->matches as $match) {
                    $rooms = [];
                    if (in_array($user->player_id, $match->open_ns_ids) || in_array($user->player_id, $match->open_ew_ids)) {
                        $rooms[] = 'open';
                    }
                    if (in_array($user->player_id, $match->closed_ns_ids) || in_array($user->player_id, $match->closed_ew_ids)) {
                        $rooms[] = 'closed';
                    }

                    foreach ($rooms as $room) {
                        $homeTeam = collect($results->teams)->firstWhere('id', $match->home_team_id);
                        $awayTeam = collect($results->teams)->firstWhere('id', $match->away_team_id);

                        $myMatches[] = [
                            'tournament' => $tournament,
                            'round' => $round,
                            'match' => $match,
                            'room' => $room,
                            'home_team' => $homeTeam,
                            'away_team' => $awayTeam,
                        ];
                    }
                }
            }
        }

        return view('scoring.index', ['matches' => $myMatches]);
    }

    /**
     * Show the mobile-friendly scorecard for a specific room.
     */
    public function showRoom(string $tournamentId, string $roundId, string $matchId, string $room): View
    {
        $user = Auth::user();
        $tournament = Tournament::findOrFail($tournamentId);
        $results = $tournament->team_results;
        
        $roundIndex = collect($results->rounds)->search(fn($r) => $r->id === $roundId);
        if ($roundIndex === false) abort(404);
        $round = $results->rounds[$roundIndex];
        
        $matchIndex = collect($round->matches)->search(fn($m) => ($m->id === $matchId || $m->home_team_id === $matchId));
        if ($matchIndex === false) abort(404);
        $match = $round->matches[$matchIndex];

        // Authorization check
        $isAuthorized = false;
        if ($room === 'open') {
            $isAuthorized = in_array($user->player_id, $match->open_ns_ids) || in_array($user->player_id, $match->open_ew_ids);
        } else {
            $isAuthorized = in_array($user->player_id, $match->closed_ns_ids) || in_array($user->player_id, $match->closed_ew_ids);
        }

        if (!$isAuthorized) abort(403);

        // Robust Board Initialization
        $numBoards = $round->boards_per_round ?? $results->boards_per_round ?? 16;
        if (count($match->boards) < $numBoards) {
            $existing = collect($match->boards)->keyBy('board_number');
            $newBoards = [];
            for ($i = 1; $i <= $numBoards; $i++) {
                $newBoards[] = $existing->get($i) ?? new MatchBoardDTO(board_number: $i);
            }
            $match->boards = $newBoards;
        }

        // Strict Isolation: Sanitize boards to only include current room data
        $sanitizedBoards = array_map(function($b) use ($room) {
            $data = $b->toArray();
            if ($room === 'open') {
                unset($data['away_contract'], $data['away_declarer'], $data['away_tricks'], $data['away_score'], $data['away_lead'], $data['away_updated_by']);
            } else {
                unset($data['home_contract'], $data['home_declarer'], $data['home_tricks'], $data['home_score'], $data['home_lead'], $data['home_updated_by']);
            }
            // Also hide IMPs
            unset($data['home_imp'], $data['away_imp']);
            return $data;
        }, $match->boards);

        $homeTeam = collect($results->teams)->firstWhere('id', $match->home_team_id);
        $awayTeam = collect($results->teams)->firstWhere('id', $match->away_team_id);

        return view('scoring.room', [
            'tournament' => $tournament,
            'round' => $round,
            'match' => $match,
            'room' => $room,
            'boards' => $sanitizedBoards,
            'homeTeam' => $homeTeam,
            'awayTeam' => $awayTeam,
        ]);
    }

    /**
     * Update a specific board result.
     */
    public function updateBoard(Request $request, string $tournamentId, string $roundId, string $matchId, string $room, int $boardNumber): array
    {
        $user = Auth::user();
        $tournament = Tournament::findOrFail($tournamentId);
        $results = $tournament->team_results;
        
        $roundIndex = collect($results->rounds)->search(fn($r) => $r->id === $roundId);
        if ($roundIndex === false) abort(404);
        $round = $results->rounds[$roundIndex];
        
        $matchIndex = collect($round->matches)->search(fn($m) => ($m->id === $matchId || $m->home_team_id === $matchId));
        if ($matchIndex === false) abort(404);
        $match = $round->matches[$matchIndex];

        // Authorization check
        $isAuthorized = false;
        if ($room === 'open') {
            $isAuthorized = in_array($user->player_id, $match->open_ns_ids) || in_array($user->player_id, $match->open_ew_ids);
        } else {
            $isAuthorized = in_array($user->player_id, $match->closed_ns_ids) || in_array($user->player_id, $match->closed_ew_ids);
        }
        if (!$isAuthorized) abort(403);

        // Robust Board Resolution
        $numBoards = $round->boards_per_round ?? $results->boards_per_round ?? 16;
        $boardsCollection = collect($match->boards);
        $boardIndex = $boardsCollection->search(fn($b) => (int)$b->board_number == (int)$boardNumber);

        if ($boardIndex === false) {
            $existing = $boardsCollection->keyBy('board_number');
            $newBoards = [];
            for ($i = 1; $i <= $numBoards; $i++) {
                $newBoards[] = $existing->get($i) ?? new MatchBoardDTO(board_number: $i);
            }
            $match->boards = $newBoards;
            $boardIndex = $boardNumber - 1; // Direct lookup for efficiency after fill
        }
        $board = $match->boards[$boardIndex];

        $data = $request->validate([
            'contract_level' => 'required|integer|min:0|max:7',
            'contract_suit' => 'required_if:contract_level,1,2,3,4,5,6,7|nullable|string|in:C,D,H,S,NT',
            'contract_risk' => 'required|integer|in:1,2,4',
            'declarer' => 'required_if:contract_level,1,2,3,4,5,6,7|nullable|string|in:N,S,E,W',
            'tricks' => 'required_if:contract_level,1,2,3,4,5,6,7|nullable|integer|min:0|max:13',
            'lead' => 'nullable|string|max:10',
        ]);

        $isVul = $this->hydrationService->calculateVulnerability($boardNumber);
        
        $level = (int) $data['contract_level'];
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

        if ($room === 'open') {
            $board->home_score = $score;
            $board->home_contract = $contract;
            $board->home_declarer = $decl;
            $board->home_tricks = $tricks;
            $board->home_lead = $data['lead'] ?? null;
            $board->home_updated_by = $user->id;
        } else {
            $board->away_score = $score;
            $board->away_contract = $contract;
            $board->away_declarer = $decl;
            $board->away_tricks = $tricks;
            $board->away_lead = $data['lead'] ?? null;
            $board->away_updated_by = $user->id;
        }

        // Recalculate board IMPs (Internal state update)
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

        $this->hydrationService->recalculateStandings($results);
        
        // Force Laravel to see the attribute as dirty by explicitly setting it
        $tournament->team_results = $results;
        $tournament->save();

        // Strict Isolation: Return only the room's updated data
        $sanitizedBoard = $board->toArray();
        if ($room === 'open') {
            unset($sanitizedBoard['away_contract'], $sanitizedBoard['away_declarer'], $sanitizedBoard['away_tricks'], $sanitizedBoard['away_score'], $sanitizedBoard['away_lead'], $sanitizedBoard['away_updated_by']);
        } else {
            unset($sanitizedBoard['home_contract'], $sanitizedBoard['home_declarer'], $sanitizedBoard['home_tricks'], $sanitizedBoard['home_score'], $sanitizedBoard['home_lead'], $sanitizedBoard['home_updated_by']);
        }
        unset($sanitizedBoard['home_imp'], $sanitizedBoard['away_imp']);

        return [
            'success' => true,
            'board' => $sanitizedBoard,
            'match_home_imp' => $match->home_imp,
            'match_away_imp' => $match->away_imp,
            'match_home_vp' => $match->home_vp,
            'match_away_vp' => $match->away_vp,
        ];
    }
}
