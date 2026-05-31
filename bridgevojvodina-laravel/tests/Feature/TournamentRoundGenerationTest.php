<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentRoundGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_generate_single_round_robin()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        
        // 4 teams
        $teamResults = [
            'teams' => [
                ['id' => 't1', 'name' => 'Team 1', 'number' => 1, 'captain_id' => 0, 'player_ids' => []],
                ['id' => 't2', 'name' => 'Team 2', 'number' => 2, 'captain_id' => 0, 'player_ids' => []],
                ['id' => 't3', 'name' => 'Team 3', 'number' => 3, 'captain_id' => 0, 'player_ids' => []],
                ['id' => 't4', 'name' => 'Team 4', 'number' => 4, 'captain_id' => 0, 'player_ids' => []],
            ],
            'rounds' => []
        ];

        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => $teamResults,
        ]);

        $response = $this->actingAs($director)->post(route('tournaments.rounds.generate', $tournament), [
            'format' => 'single_round_robin',
            'boards_per_round' => 16,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $tournament->refresh();
        $results = $tournament->team_results;
        
        // 4 teams -> 3 rounds
        $this->assertCount(3, $results->rounds);
        
        // Check Round 1 Berger pairings: (1, 4), (2, 3)
        $r1 = $results->rounds[0];
        $this->assertCount(2, $r1->matches);
        
        // Match 1: 4 vs 1 (Fixed team 4 is odd round -> Home)
        $m1 = $r1->matches[0];
        $this->assertEquals('t4', $m1->home_team_id);
        $this->assertEquals('t1', $m1->away_team_id);

        // Match 2: 3 vs 2
        $m2 = $r1->matches[1];
        $this->assertEquals('t3', $m2->home_team_id);
        $this->assertEquals('t2', $m2->away_team_id);
    }

    public function test_director_can_generate_double_round_robin()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        
        $teamResults = [
            'teams' => [
                ['id' => 't1', 'name' => 'Team 1', 'number' => 1, 'captain_id' => 0, 'player_ids' => []],
                ['id' => 't2', 'name' => 'Team 2', 'number' => 2, 'captain_id' => 0, 'player_ids' => []],
            ],
            'rounds' => []
        ];

        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => $teamResults,
        ]);

        $response = $this->actingAs($director)->post(route('tournaments.rounds.generate', $tournament), [
            'format' => 'double_round_robin',
            'boards_per_round' => 16,
        ]);

        $tournament->refresh();
        $results = $tournament->team_results;
        
        // 2 teams -> 2 rounds for double
        $this->assertCount(2, $results->rounds);
        
        // Round 1: 2 vs 1
        $this->assertEquals('t2', $results->rounds[0]->matches[0]->home_team_id);
        $this->assertEquals('t1', $results->rounds[0]->matches[0]->away_team_id);

        // Round 2: 1 vs 2 (Swapped)
        $this->assertEquals('t1', $results->rounds[1]->matches[0]->home_team_id);
        $this->assertEquals('t2', $results->rounds[1]->matches[0]->away_team_id);
    }

    public function test_director_can_append_rounds()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        
        $teamResults = [
            'teams' => [
                ['id' => 't1', 'name' => 'Team 1', 'number' => 1, 'captain_id' => 0, 'player_ids' => []],
                ['id' => 't2', 'name' => 'Team 2', 'number' => 2, 'captain_id' => 0, 'player_ids' => []],
            ],
            'rounds' => [
                ['id' => 'existing', 'name' => 'Round 1', 'status' => 'complete', 'matches' => [], 'board_set_id' => null]
            ]
        ];

        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => $teamResults,
        ]);

        $this->actingAs($director)->post(route('tournaments.rounds.generate', $tournament), [
            'format' => 'single_round_robin',
            'boards_per_round' => 16,
        ]);

        $tournament->refresh();
        // 1 existing + 1 new = 2 rounds
        $this->assertCount(2, $tournament->team_results->rounds);
        $this->assertEquals('Round 1', $tournament->team_results->rounds[0]->name);
        $this->assertEquals('Round 2', $tournament->team_results->rounds[1]->name);
    }

    public function test_director_can_delete_idle_round()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => [
                'rounds' => [
                    ['id' => 'r1', 'name' => 'R1', 'status' => 'idle']
                ],
                'teams' => []
            ]
        ]);

        $response = $this->actingAs($director)->delete(route('tournaments.rounds.destroy', [$tournament, 'r1']));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $tournament->refresh();
        $this->assertCount(0, $tournament->team_results->rounds);
    }

    public function test_director_cannot_delete_active_round()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => [
                'rounds' => [
                    ['id' => 'r1', 'name' => 'R1', 'status' => 'inProgress']
                ],
                'teams' => []
            ]
        ]);

        $response = $this->actingAs($director)->delete(route('tournaments.rounds.destroy', [$tournament, 'r1']));

        $response->assertSessionHasErrors();
        
        $tournament->refresh();
        $this->assertCount(1, $tournament->team_results->rounds);
    }

    public function test_director_can_delete_all_idle_rounds()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => [
                'rounds' => [
                    ['id' => 'r1', 'name' => 'R1', 'status' => 'complete'],
                    ['id' => 'r2', 'name' => 'R2', 'status' => 'idle'],
                    ['id' => 'r3', 'name' => 'R3', 'status' => 'idle'],
                ],
                'teams' => []
            ]
        ]);

        $response = $this->actingAs($director)->delete(route('tournaments.rounds.idle.destroy', $tournament));

        $response->assertRedirect();
        $tournament->refresh();
        $this->assertCount(1, $tournament->team_results->rounds);
        $this->assertEquals('complete', $tournament->team_results->rounds[0]->status);
    }

    public function test_director_can_reorder_idle_rounds()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => [
                'rounds' => [
                    ['id' => 'r1', 'name' => 'First', 'status' => 'idle'],
                    ['id' => 'r2', 'name' => 'Second', 'status' => 'idle'],
                ],
                'teams' => []
            ]
        ]);

        // Move Second UP
        $this->actingAs($director)->patch(route('tournaments.rounds.reorder', [$tournament, 'r2']), [
            'direction' => 'up',
        ]);

        $tournament->refresh();
        $this->assertEquals('r2', $tournament->team_results->rounds[0]->id);
        $this->assertEquals('r1', $tournament->team_results->rounds[1]->id);
    }

    public function test_director_can_upload_rounds_csv()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        
        $teamResults = [
            'teams' => [
                ['id' => 'team1', 'name' => 'T1', 'number' => 1, 'captain_id' => 0, 'player_ids' => []],
                ['id' => 'team2', 'name' => 'T2', 'number' => 2, 'captain_id' => 0, 'player_ids' => []],
            ],
            'rounds' => []
        ];

        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => $teamResults,
        ]);

        $csvContent = "Round Name,Home Team Number,Away Team Number\n";
        $csvContent .= "Round A,1,2\n";

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('rounds.csv', $csvContent);

        $this->actingAs($director)->post(route('tournaments.rounds.upload-csv', $tournament), [
            'csv_file' => $file,
            'boards_per_round' => 16,
        ]);

        $tournament->refresh();
        $this->assertCount(1, $tournament->team_results->rounds);
        $this->assertEquals('Round A', $tournament->team_results->rounds[0]->name);
        $this->assertEquals('team1', $tournament->team_results->rounds[0]->matches[0]->home_team_id);
        $this->assertEquals('team2', $tournament->team_results->rounds[0]->matches[0]->away_team_id);
    }

    public function test_director_can_update_bye_vp()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        
        $teamResults = [
            'teams' => [
                ['id' => 't1', 'name' => 'T1', 'number' => 1, 'total_vp' => 0, 'captain_id' => 0, 'player_ids' => []]
            ],
            'rounds' => [
                [
                    'id' => 'r1', 'name' => 'R1', 'status' => 'idle', 'board_set_id' => null,
                    'matches' => [
                        ['id' => 'm1', 'home_team_id' => 't1', 'away_team_id' => null, 'home_vp' => 12, 'away_vp' => 0, 'home_imp' => 0, 'away_imp' => 0, 'boards' => []]
                    ]
                ]
            ],
            'bye_vp' => 12.0
        ];

        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => $teamResults,
        ]);

        $response = $this->actingAs($director)->patch(route('tournaments.settings.update', $tournament), [
            'bye_vp' => 15.5,
            'boards_per_round' => 12,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $tournament->refresh();
        $this->assertEquals(15.5, $tournament->team_results->bye_vp);
        $this->assertEquals(12, $tournament->team_results->boards_per_round);
        $this->assertEquals(15.5, $tournament->team_results->rounds[0]->matches[0]->home_vp);
        $this->assertEquals(15.5, $tournament->team_results->teams[0]->total_vp);
    }

    public function test_director_can_enter_match_results_for_inprogress_round()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $club = \App\Models\Club::create(['name' => 'C1', 'city' => 'C', 'address' => 'A', 'representative' => 'R', 'email' => 'e@e.com', 'phone' => '1', 'status' => 'Active']);
        $p1 = \App\Models\Player::create(['first_name' => 'P1', 'last_name' => 'L1', 'club_id' => $club->id]);
        $p2 = \App\Models\Player::create(['first_name' => 'P2', 'last_name' => 'L2', 'club_id' => $club->id]);

        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => [
                'teams' => [
                    ['id' => 't1', 'name' => 'T1', 'number' => 1, 'player_ids' => [$p1->id], 'captain_id' => $p1->id, 'total_vp' => 0],
                    ['id' => 't2', 'name' => 'T2', 'number' => 2, 'player_ids' => [$p2->id], 'captain_id' => $p2->id, 'total_vp' => 0],
                ],
                'rounds' => [
                    [
                        'id' => 'r1', 'name' => 'R1', 'status' => 'inProgress',
                        'matches' => [
                            ['id' => 'm1', 'home_team_id' => 't1', 'away_team_id' => 't2', 'home_vp' => 0, 'away_vp' => 0, 'home_imp' => 0, 'away_imp' => 0, 'boards' => [], 'open_ns_ids' => [], 'open_ew_ids' => [], 'closed_ns_ids' => [], 'closed_ew_ids' => []]
                        ]
                    ]
                ]
            ]
        ]);

        $response = $this->actingAs($director)->patch(route('tournaments.match.update', [$tournament, 'r1', 'm1']), [
            'home_imp' => 50,
            'away_imp' => 20,
            'home_vp' => 17.5, // This should be ignored/overwritten by auto-calc
            'away_vp' => 2.5,  // This should be ignored/overwritten by auto-calc
            'open_n_id' => $p1->id,
            'open_s_id' => null,
            'open_e_id' => $p2->id,
            'open_w_id' => null,
            'closed_e_id' => $p1->id,
            'closed_w_id' => null,
            'closed_n_id' => $p2->id,
            'closed_s_id' => null,
        ]);

        $response->assertRedirect(route('tournaments.edit', $tournament));
        
        $tournament->refresh();
        $match = $tournament->team_results->rounds[0]->matches[0];
        $this->assertEquals(50, $match->home_imp);
        // 50-20 = 30 IMP diff. For 16 boards, WBF continuous is ~16.73
        $this->assertEquals(16.73, $match->home_vp);
        $this->assertContains($p1->id, $match->open_ns_ids);
        
        // Standings updated
        $this->assertEquals(16.73, $tournament->team_results->teams[0]->total_vp);
        $this->assertEquals(3.27, $tournament->team_results->teams[1]->total_vp);
    }
}
