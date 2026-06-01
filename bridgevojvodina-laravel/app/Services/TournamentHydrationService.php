<?php

namespace App\Services;

use App\DTOs\Tournament\TournamentResultsDTO;
use App\Models\Player;

class TournamentHydrationService
{
    public function __construct(
        protected VpCalculationService $vpService
    ) {}

    public function hydratePlayers(TournamentResultsDTO $results): void
    {
        $playerIds = [];

        // Collect all unique player IDs
        foreach ($results->teams as $team) {
            foreach ($team->player_ids as $id) {
                $playerIds[] = $id;
            }
        }

        foreach ($results->rounds as $round) {
            foreach ($round->matches as $match) {
                foreach ($match->home_lineup as $lineupPlayer) {
                    $playerIds[] = $lineupPlayer->player_id;
                }
                foreach ($match->away_lineup as $lineupPlayer) {
                    $playerIds[] = $lineupPlayer->player_id;
                }
                $playerIds = array_merge($playerIds, $match->open_ns_ids, $match->open_ew_ids, $match->closed_ns_ids, $match->closed_ew_ids);
            }
        }

        $playerIds = array_unique(array_filter($playerIds));

        if (empty($playerIds)) {
            return;
        }

        // Fetch players in bulk
        $players = Player::whereIn('id', $playerIds)->get()->keyBy('id');

        // Attach player models back to the DTOs
        // (Note: This is partially symbolic for teams, but crucial for lineups)
        foreach ($results->rounds as $round) {
            $this->hydrateMatches($round->matches, $players);
        }
    }

    public function hydrateMatch(\App\DTOs\Tournament\MatchDTO $match): void
    {
        $playerIds = [];
        foreach ($match->home_lineup as $lineupPlayer) {
            $playerIds[] = $lineupPlayer->player_id;
        }
        foreach ($match->away_lineup as $lineupPlayer) {
            $playerIds[] = $lineupPlayer->player_id;
        }
        $playerIds = array_merge($playerIds, $match->open_ns_ids, $match->open_ew_ids, $match->closed_ns_ids, $match->closed_ew_ids);

        $playerIds = array_unique(array_filter($playerIds));

        if (empty($playerIds)) {
            return;
        }

        $players = Player::whereIn('id', $playerIds)->get()->keyBy('id');
        $this->hydrateMatchData($match, $players);
    }

    protected function hydrateMatches(array $matches, $players): void
    {
        foreach ($matches as $match) {
            $this->hydrateMatchData($match, $players);
        }
    }

    protected function hydrateMatchData(\App\DTOs\Tournament\MatchDTO $match, $players): void
    {
        foreach ($match->home_lineup as $lineupPlayer) {
            $lineupPlayer->player = $players->get($lineupPlayer->player_id);
        }
        foreach ($match->away_lineup as $lineupPlayer) {
            $lineupPlayer->player = $players->get($lineupPlayer->player_id);
        }

        $match->open_ns = array_map(fn($id) => $players->get($id), $match->open_ns_ids);
        $match->open_ew = array_map(fn($id) => $players->get($id), $match->open_ew_ids);
        $match->closed_ns = array_map(fn($id) => $players->get($id), $match->closed_ns_ids);
        $match->closed_ew = array_map(fn($id) => $players->get($id), $match->closed_ew_ids);
    }

    public function getBoardData(\App\DTOs\Tournament\RoundDTO $round, int $boardNumber): array
    {
        $dealer = $this->calculateDealer($boardNumber);
        $vulnerability = $this->calculateVulnerability($boardNumber);
        $physicalBoard = null;

        if ($round->board_set_id) {
            $physicalBoard = \App\Models\Board::where('board_set_id', $round->board_set_id)
                ->where('board_number', $boardNumber)
                ->first();
        }

        return [
            'dealer' => $dealer,
            'vulnerability' => $vulnerability,
            'physical_board' => $physicalBoard,
        ];
    }

    public function scoreToImp(int $score): int
    {
        $absScore = abs($score);
        $sign = $score <=> 0;

        $impScale = [
            20, 50, 90, 130, 170, 220, 270, 320, 370, 430, 500, 600, 750, 900, 1100, 1300, 1500, 1750, 2000, 2250, 2500, 3000, 3500, 4000
        ];

        $imps = 0;
        foreach ($impScale as $threshold) {
            if ($absScore >= $threshold) {
                $imps++;
            } else {
                break;
            }
        }

        return $imps * $sign;
    }

    protected function calculateDealer(int $boardNumber): string
    {
        $dealers = ['N', 'E', 'S', 'W'];
        return $dealers[($boardNumber - 1) % 4];
    }

    public function calculateVulnerability(int $boardNumber): string
    {
        $vulns = [
            'None', 'NS', 'EW', 'All',
            'NS', 'EW', 'All', 'None',
            'EW', 'All', 'None', 'NS',
            'All', 'None', 'NS', 'EW'
        ];
        return $vulns[($boardNumber - 1) % 16];
    }

    /**
     * Calculate Butler IMPs for each player in the tournament.
     * @return \App\DTOs\Tournament\PlayerButlerDTO[]
     */
    public function calculatePlayerButlers(TournamentResultsDTO $results): array
    {
        $playerStats = []; // [player_id => ['total_imps' => 0, 'boards' => 0]]

        // 1. Gather all boards and their NS scores to calculate datum
        $boardScores = []; // [board_number => [score1, score2, ...]]

        foreach ($results->rounds as $round) {
            foreach ($round->matches as $match) {
                foreach ($match->boards as $board) {
                    if ($board->home_score !== null) {
                        $boardScores[$board->board_number][] = $board->home_score;
                    }
                    if ($board->away_score !== null) {
                        $boardScores[$board->board_number][] = $board->away_score;
                    }
                }
            }
        }

        // 2. Calculate datum per board
        $datums = [];
        foreach ($boardScores as $boardNum => $scores) {
            if (!empty($scores)) {
                $datums[$boardNum] = array_sum($scores) / count($scores);
            }
        }

        // 3. Calculate IMPs relative to datum for each seat
        foreach ($results->rounds as $round) {
            foreach ($round->matches as $match) {
                foreach ($match->boards as $board) {
                    $datum = $datums[$board->board_number] ?? null;
                    if ($datum === null) continue;

                    // Open Room
                    if ($board->home_score !== null) {
                        $imp = $this->scoreToImp($board->home_score - $datum);
                        $this->assignImpsToPlayers($playerStats, $match->open_ns_ids, $imp);
                        $this->assignImpsToPlayers($playerStats, $match->open_ew_ids, -$imp);
                    }

                    // Closed Room
                    if ($board->away_score !== null) {
                        $imp = $this->scoreToImp($board->away_score - $datum);
                        $this->assignImpsToPlayers($playerStats, $match->closed_ns_ids, $imp);
                        $this->assignImpsToPlayers($playerStats, $match->closed_ew_ids, -$imp);
                    }
                }
            }
        }

        // 4. Convert to DTOs
        $butlers = [];
        foreach ($playerStats as $playerId => $data) {
            $butlers[] = new \App\DTOs\Tournament\PlayerButlerDTO(
                player_id: $playerId,
                boards_played: $data['boards'],
                total_imps: (float) $data['total_imps'],
                imps_per_board: $data['boards'] > 0 ? (float) ($data['total_imps'] / $data['boards']) : 0.0
            );
        }

        return $butlers;
    }

    protected function assignImpsToPlayers(array &$stats, array $playerIds, int $imp): void
    {
        foreach ($playerIds as $id) {
            if (!$id) continue;
            if (!isset($stats[$id])) {
                $stats[$id] = ['total_imps' => 0, 'boards' => 0];
            }
            $stats[$id]['total_imps'] += $imp;
            $stats[$id]['boards'] += 1;
        }
    }

    public function recalculateStandings(TournamentResultsDTO $results): void
    {
        // Reset all team VPs
        foreach ($results->teams as $team) {
            $team->total_vp = 0;
        }

        // Process all matches in all rounds
        foreach ($results->rounds as $round) {
            $boards = $round->boards_per_round ?? $results->boards_per_round ?? 16;
            
            foreach ($round->matches as $match) {
                // Determine if it's a bye and award configured bye VP
                $isHomeBye = empty($match->home_team_id) || $match->home_team_id === 'bye';
                $isAwayBye = empty($match->away_team_id) || $match->away_team_id === 'bye';

                if ($isHomeBye || $isAwayBye) {
                    if (!$isHomeBye) {
                        $match->home_vp = (float)($results->bye_vp ?? 12.0);
                        $match->home_imp = 0;
                        $match->away_vp = 0;
                        $match->away_imp = 0;
                    } elseif (!$isAwayBye) {
                        $match->away_vp = (float)($results->bye_vp ?? 12.0);
                        $match->away_imp = 0;
                        $match->home_vp = 0;
                        $match->home_imp = 0;
                    }
                } else {
                    // Automatically calculate VP based on IMPs for normal matches
                    if ($match->home_imp !== 0 || $match->away_imp !== 0) {
                        list($hVp, $aVp) = $this->vpService->calculateVp($match->home_imp, $match->away_imp, $boards);
                        $match->home_vp = $hVp;
                        $match->away_vp = $aVp;
                    }
                }

                // Add VPs to teams ONLY if the round is complete
                if ($round->status === 'complete') {
                    if (!$isHomeBye) {
                        $team = collect($results->teams)->firstWhere('id', $match->home_team_id);
                        if ($team) $team->total_vp += (float)$match->home_vp;
                    }
                    if (!$isAwayBye) {
                        $team = collect($results->teams)->firstWhere('id', $match->away_team_id);
                        if ($team) $team->total_vp += (float)$match->away_vp;
                    }
                }
            }
        }
    }
}
