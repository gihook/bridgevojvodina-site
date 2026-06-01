<?php

namespace Tests\Feature;

use App\Models\TournamentConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_add_and_delete_team()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = TournamentConfiguration::create([
            'title' => 'Test Tournament',
            'user_id' => $director->id,
            'team_results' => [
                'teams' => [],
                'rounds' => [],
            ],
        ]);

        // Add team
        $response = $this->actingAs($director)->post(route('tournaments.teams.add', $tournament), [
            'name' => 'New Team'
        ]);

        $response->assertRedirect();
        $tournament->refresh();
        $this->assertCount(1, $tournament->team_results->teams);
        $this->assertEquals('New Team', $tournament->team_results->teams[0]->name);

        $teamId = $tournament->team_results->teams[0]->id;

        // Delete team
        $response = $this->actingAs($director)->delete(route('tournaments.teams.destroy', [$tournament, $teamId]));

        $response->assertRedirect();
        $tournament->refresh();
        $this->assertCount(0, $tournament->team_results->teams);
    }
}
