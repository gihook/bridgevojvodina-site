<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use App\Models\BoardSet;
use App\Models\Board;
use App\DTOs\Tournament\MatchBoardDTO;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardRenumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_renumber_boards_in_round()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        
        $teamResults = [
            'teams' => [
                ['id' => 'team1', 'name' => 'Team 1', 'number' => 1, 'captain_id' => 1, 'player_ids' => [1, 2]],
                ['id' => 'team2', 'name' => 'Team 2', 'number' => 2, 'captain_id' => 3, 'player_ids' => [3, 4]]
            ],
            'rounds' => [
                [
                    'id' => 'round1', 
                    'name' => 'Round 1', 
                    'status' => 'inProgress',
                    'matches' => [
                        [
                            'id' => 'match1',
                            'home_team_id' => 'team1',
                            'away_team_id' => 'team2',
                            'home_imp' => 0, 'away_imp' => 0, 'home_vp' => 0, 'away_vp' => 0,
                            'boards' => [
                                ['board_number' => 1, 'home_score' => 420],
                                ['board_number' => 2, 'home_score' => -50]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => $teamResults,
        ]);

        $boardSet = BoardSet::create([
            'tournament_id' => $tournament->id,
            'name' => 'Test Set'
        ]);

        Board::create([
            'board_set_id' => $boardSet->id,
            'board_number' => 1,
            'vulnerability' => 'None',
            'cards_north' => ['S' => 'A', 'H' => '', 'D' => '', 'C' => ''],
            'cards_south' => ['S' => '', 'H' => '', 'D' => '', 'C' => ''],
            'cards_east' => ['S' => '', 'H' => '', 'D' => '', 'C' => ''],
            'cards_west' => ['S' => '', 'H' => '', 'D' => '', 'C' => ''],
        ]);

        // Link board set to round
        $results = $tournament->team_results;
        $results->rounds[0]->board_set_id = $boardSet->id;
        $tournament->team_results = $results;
        $tournament->save();

        $response = $this->actingAs($director)->post(route('tournaments.rounds.renumber', [$tournament, 'round1']), [
            'starting_board_number' => 17,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $tournament->refresh();
        $updatedResults = $tournament->team_results;
        $this->assertEquals(17, $updatedResults->rounds[0]->matches[0]->boards[0]->board_number);
        $this->assertEquals(18, $updatedResults->rounds[0]->matches[0]->boards[1]->board_number);

        $this->assertDatabaseHas('boards', [
            'board_set_id' => $boardSet->id,
            'board_number' => 17,
        ]);
    }
}
