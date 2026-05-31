<?php

namespace App\Services;

use App\DTOs\Tournament\TournamentResultsDTO;
use App\Models\Player;

class TournamentHydrationService
{
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

    protected function calculateVulnerability(int $boardNumber): string
    {
        $vulns = [
            'None', 'NS', 'EW', 'All',
            'NS', 'EW', 'All', 'None',
            'EW', 'All', 'None', 'NS',
            'All', 'None', 'NS', 'EW'
        ];
        return $vulns[($boardNumber - 1) % 16];
    }
}
