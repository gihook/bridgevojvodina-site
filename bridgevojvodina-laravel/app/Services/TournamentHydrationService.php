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
            foreach ($round->matches as $match) {
                foreach ($match->home_lineup as $lineupPlayer) {
                    $lineupPlayer->player = $players->get($lineupPlayer->player_id);
                }
                foreach ($match->away_lineup as $lineupPlayer) {
                    $lineupPlayer->player = $players->get($lineupPlayer->player_id);
                }
            }
        }
    }
}
