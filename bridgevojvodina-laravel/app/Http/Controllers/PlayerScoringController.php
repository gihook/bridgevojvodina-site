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
                if (($round->status ?? 'idle') !== 'inProgress') continue;
                foreach ($round->matches as $match) {
                    if (($match->status ?? 'pending') === 'inProgress' && self::playerCanUseMatch((int) $user->player_id, $results, $match)) {
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
            return view('scoring.index', [
                'matches' => [],
                'players' => $this->availablePlayers(),
                'error' => __('You must be linked to a player to enter scores.'),
            ]);
        }

        $activeTournaments = Tournament::where('is_completed', false)->get();
        $myMatches = [];

        foreach ($activeTournaments as $tournament) {
            $results = $tournament->team_results;
            if (!$results) continue;

            foreach ($results->rounds as $round) {
                if (($round->status ?? 'idle') !== 'inProgress') continue;

                foreach ($round->matches as $match) {
                    if (($match->status ?? 'pending') !== 'inProgress') continue;

                    $teamSide = self::playerTeamSide((int) $user->player_id, $results, $match);
                    if (!$teamSide) {
                        continue;
                    }

                    $playerIds = collect([
                        ...($match->open_ns_ids ?? []),
                        ...($match->open_ew_ids ?? []),
                        ...($match->closed_ns_ids ?? []),
                        ...($match->closed_ew_ids ?? []),
                    ])->filter()->unique()->values();

                    $myMatches[] = [
                        'tournament' => $tournament,
                        'round' => $round,
                        'match' => $match,
                        'home_team' => collect($results->teams)->firstWhere('id', $match->home_team_id),
                        'away_team' => collect($results->teams)->firstWhere('id', $match->away_team_id),
                        'team_side' => $teamSide,
                        'rooms' => $this->buildRoomSeatingData($match, (int) $user->player_id, $teamSide, Player::whereIn('id', $playerIds)->get()->keyBy('id')),
                    ];
                }
            }
        }

        return view('scoring.index', [
            'matches' => $myMatches,
            'linkedPlayer' => $user->player,
        ]);
    }

    public function linkPlayer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'player_id' => 'required|integer|exists:players,id',
        ]);

        $user = Auth::user();
        $playerId = (int) $data['player_id'];

        $alreadyLinked = \App\Models\User::where('player_id', $playerId)
            ->where('id', '!=', $user->id)
            ->exists();

        if ($alreadyLinked) {
            return back()->withErrors(['player_id' => __('This player is already connected to another account.')]);
        }

        $user->player_id = $playerId;
        $user->save();

        return redirect()->route('scoring.index')->with('success', __('Player connected successfully.'));
    }

    public function sit(Request $request, string $tournamentId, string $roundId, string $matchId): RedirectResponse
    {
        $data = $request->validate([
            'room' => 'required|string|in:open,closed',
            'position' => 'nullable|string|in:N,S,E,W',
            'enter_after_sit' => 'nullable|boolean',
        ]);

        [$tournament, $results, $round, $match] = $this->resolveOpenMatch($tournamentId, $roundId, $matchId);
        $playerId = (int) Auth::user()->player_id;
        if (!$playerId) {
            abort(403);
        }

        $teamSide = self::playerTeamSide($playerId, $results, $match);
        $position = $data['position'] ?? $this->firstAvailablePosition($match, $playerId, $data['room'], $teamSide);
        if (!$position) {
            return back()->withErrors(['seat' => __('No seats are available for your team in that room.')]);
        }

        if (!$teamSide || self::seatTeamSide($data['room'], $position) !== $teamSide) {
            return back()->withErrors(['seat' => __('You can only sit in seats assigned to your team.')]);
        }

        $seat = self::seatReference($data['room'], $position);
        $match->{$seat['property']} = self::normalizeSeatPair($match->{$seat['property']} ?? []);

        $occupiedBy = $match->{$seat['property']}[$seat['index']] ?? null;
        if ($occupiedBy && (int) $occupiedBy !== $playerId) {
            return back()->withErrors(['seat' => __('That seat is already taken.')]);
        }

        $this->removePlayerFromMatchSeats($match, $playerId);
        $match->{$seat['property']} = self::normalizeSeatPair($match->{$seat['property']} ?? []);
        $match->{$seat['property']}[$seat['index']] = $playerId;

        $tournament->team_results = $results;
        $tournament->save();

        if ($request->boolean('enter_after_sit')) {
            return redirect()->route('scoring.room.show', [$tournament, $round->id, ($match->id ?: $match->home_team_id), $data['room']])
                ->with('success', __('You are seated.'));
        }

        return back()->with('success', __('You are seated.'));
    }

    public function leave(string $tournamentId, string $roundId, string $matchId): RedirectResponse
    {
        [$tournament, $results, , $match] = $this->resolveOpenMatch($tournamentId, $roundId, $matchId);
        $playerId = (int) Auth::user()->player_id;
        if (!$playerId) {
            abort(403);
        }

        $this->removePlayerFromMatchSeats($match, $playerId);

        $tournament->team_results = $results;
        $tournament->save();

        return back()->with('success', __('You left the table.'));
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

        if (($round->status ?? 'idle') !== 'inProgress' || ($match->status ?? 'pending') !== 'inProgress') {
            abort(403, __('This match is not open for scoring.'));
        }

        if (!$user->player_id || !self::playerIsInMatch((int) $user->player_id, $match, $room)) abort(403);

        // Robust Board Initialization
        $numBoards = $this->matchBoardsCount($match, $round, $results);
        if (count($match->boards) !== $numBoards) {
            $this->resizeMatchBoards($match, $numBoards);
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

        if (($round->status ?? 'idle') !== 'inProgress' || ($match->status ?? 'pending') !== 'inProgress') {
            abort(403, __('This match is not open for scoring.'));
        }
        if (!$user->player_id || !self::playerIsInMatch((int) $user->player_id, $match, $room)) abort(403);

        // Robust Board Resolution
        $numBoards = $this->matchBoardsCount($match, $round, $results);
        if ($boardNumber < 1 || $boardNumber > $numBoards) {
            abort(404);
        }

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

        $boardsCount = $this->matchBoardsCount($match, $round, $results);
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
        ];
    }

    protected function availablePlayers()
    {
        return Player::with('club')
            ->whereDoesntHave('user')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    protected function matchBoardsCount(object $match, object $round, object $results): int
    {
        return (int) ($match->boards_count ?? $round->boards_per_round ?? $results->boards_per_round ?? 16);
    }

    protected function resizeMatchBoards(object $match, int $boardsCount): void
    {
        $existing = collect($match->boards ?? [])->keyBy('board_number');
        $boards = [];

        for ($i = 1; $i <= $boardsCount; $i++) {
            $boards[] = $existing->get($i) ?? new MatchBoardDTO(board_number: $i);
        }

        $match->boards = $boards;
    }

    protected function resolveOpenMatch(string $tournamentId, string $roundId, string $matchId): array
    {
        $tournament = Tournament::findOrFail($tournamentId);
        $results = $tournament->team_results;
        if (!$results) abort(404);

        $roundIndex = collect($results->rounds)->search(fn($r) => $r->id === $roundId);
        if ($roundIndex === false) abort(404);
        $round = $results->rounds[$roundIndex];

        $matchIndex = collect($round->matches)->search(fn($m) => ($m->id === $matchId || $m->home_team_id === $matchId));
        if ($matchIndex === false) abort(404);
        $match = $round->matches[$matchIndex];

        if (($round->status ?? 'idle') !== 'inProgress' || ($match->status ?? 'pending') !== 'inProgress') {
            abort(403, __('This match is not open for scoring.'));
        }

        return [$tournament, $results, $round, $match];
    }

    protected function buildRoomSeatingData(object $match, int $playerId, string $teamSide, $players): array
    {
        $rooms = [];

        foreach (['open' => __('Open Room'), 'closed' => __('Closed Room')] as $room => $label) {
            $seats = [];
            foreach (['N', 'S', 'E', 'W'] as $position) {
                $seat = self::seatReference($room, $position);
                $pair = self::normalizeSeatPair($match->{$seat['property']} ?? []);
                $occupantId = $pair[$seat['index']] ?? null;
                $occupant = $occupantId ? $players->get((int) $occupantId) : null;
                $seatTeamSide = self::seatTeamSide($room, $position);

                $seats[$position] = [
                    'position' => $position,
                    'occupant_id' => $occupantId ? (int) $occupantId : null,
                    'occupant_name' => $occupant ? trim($occupant->first_name . ' ' . $occupant->last_name) : null,
                    'is_mine' => $occupantId && (int) $occupantId === $playerId,
                    'can_sit' => $seatTeamSide === $teamSide && (!$occupantId || (int) $occupantId === $playerId),
                    'team_side' => $seatTeamSide,
                ];
            }

            $rooms[$room] = [
                'key' => $room,
                'label' => $label,
                'seats' => $seats,
                'is_seated' => self::playerIsInMatch($playerId, $match, $room),
            ];
        }

        return $rooms;
    }

    protected function removePlayerFromMatchSeats(object $match, int $playerId): void
    {
        foreach (['open_ns_ids', 'open_ew_ids', 'closed_ns_ids', 'closed_ew_ids'] as $property) {
            $pair = self::normalizeSeatPair($match->{$property} ?? []);
            foreach ($pair as $index => $id) {
                if ($id && (int) $id === $playerId) {
                    $pair[$index] = null;
                }
            }
            $match->{$property} = $pair;
        }
    }

    protected function firstAvailablePosition(object $match, int $playerId, string $room, ?string $teamSide): ?string
    {
        if (!$teamSide) {
            return null;
        }

        foreach (['N', 'S', 'E', 'W'] as $position) {
            if (self::seatTeamSide($room, $position) !== $teamSide) {
                continue;
            }

            $seat = self::seatReference($room, $position);
            $pair = self::normalizeSeatPair($match->{$seat['property']} ?? []);
            $occupantId = $pair[$seat['index']] ?? null;

            if ($occupantId && (int) $occupantId === $playerId) {
                return $position;
            }
        }

        foreach (['N', 'S', 'E', 'W'] as $position) {
            if (self::seatTeamSide($room, $position) !== $teamSide) {
                continue;
            }

            $seat = self::seatReference($room, $position);
            $pair = self::normalizeSeatPair($match->{$seat['property']} ?? []);
            $occupantId = $pair[$seat['index']] ?? null;

            if (!$occupantId) {
                return $position;
            }
        }

        return null;
    }

    public static function publicRoomAction(int $playerId, object $results, object $round, object $match, string $room): ?array
    {
        if (($round->status ?? 'idle') !== 'inProgress' || ($match->status ?? 'pending') !== 'inProgress') {
            return null;
        }

        $teamSide = self::playerTeamSide($playerId, $results, $match);
        if (!$teamSide) {
            return null;
        }

        $isSeated = self::playerIsInMatch($playerId, $match, $room);
        $hasAvailableSeat = false;

        foreach (['N', 'S', 'E', 'W'] as $position) {
            if (self::seatTeamSide($room, $position) !== $teamSide) {
                continue;
            }

            $seat = self::seatReference($room, $position);
            $pair = self::normalizeSeatPair($match->{$seat['property']} ?? []);
            $occupantId = $pair[$seat['index']] ?? null;

            if (!$occupantId || (int) $occupantId === $playerId) {
                $hasAvailableSeat = true;
                break;
            }
        }

        if (!$hasAvailableSeat) {
            return null;
        }

        return [
            'is_seated' => $isSeated,
            'label' => $isSeated ? __('Enter') : __('Sit'),
        ];
    }

    public static function playerCanUseMatch(int $playerId, object $results, object $match): bool
    {
        return self::playerIsInMatch($playerId, $match) || self::playerTeamSide($playerId, $results, $match) !== null;
    }

    public static function playerTeamSide(int $playerId, object $results, object $match): ?string
    {
        $homeTeam = collect($results->teams)->firstWhere('id', $match->home_team_id);
        $awayTeam = collect($results->teams)->firstWhere('id', $match->away_team_id);

        if ($homeTeam && in_array($playerId, array_map('intval', $homeTeam->player_ids ?? []), true)) {
            return 'home';
        }

        if ($awayTeam && in_array($playerId, array_map('intval', $awayTeam->player_ids ?? []), true)) {
            return 'away';
        }

        return null;
    }

    public static function seatTeamSide(string $room, string $position): string
    {
        $isNs = in_array($position, ['N', 'S'], true);

        if ($room === 'open') {
            return $isNs ? 'home' : 'away';
        }

        return $isNs ? 'away' : 'home';
    }

    public static function seatReference(string $room, string $position): array
    {
        return match ($room . ':' . $position) {
            'open:N' => ['property' => 'open_ns_ids', 'index' => 0],
            'open:S' => ['property' => 'open_ns_ids', 'index' => 1],
            'open:E' => ['property' => 'open_ew_ids', 'index' => 0],
            'open:W' => ['property' => 'open_ew_ids', 'index' => 1],
            'closed:N' => ['property' => 'closed_ns_ids', 'index' => 0],
            'closed:S' => ['property' => 'closed_ns_ids', 'index' => 1],
            'closed:E' => ['property' => 'closed_ew_ids', 'index' => 0],
            'closed:W' => ['property' => 'closed_ew_ids', 'index' => 1],
        };
    }

    public static function normalizeSeatPair(array $ids): array
    {
        return [
            isset($ids[0]) && $ids[0] !== '' ? (int) $ids[0] : null,
            isset($ids[1]) && $ids[1] !== '' ? (int) $ids[1] : null,
        ];
    }

    public static function playerIsInMatch(int $playerId, object $match, ?string $room = null): bool
    {
        $openIds = array_merge($match->open_ns_ids ?? [], $match->open_ew_ids ?? []);
        $closedIds = array_merge($match->closed_ns_ids ?? [], $match->closed_ew_ids ?? []);

        $normalize = fn(array $ids) => array_map('intval', $ids);

        if ($room === 'open') {
            return in_array($playerId, $normalize($openIds), true);
        }

        if ($room === 'closed') {
            return in_array($playerId, $normalize($closedIds), true);
        }

        return in_array($playerId, $normalize(array_merge($openIds, $closedIds)), true);
    }
}
