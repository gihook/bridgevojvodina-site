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
}
