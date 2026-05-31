<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use App\Models\Player;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentTeamTest extends TestCase
{
    use RefreshDatabase;

    private function createTournamentWithTeams($director)
    {
        return Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => [
                'teams' => [
                    ['id' => 't1', 'name' => 'Team A', 'captain_id' => 0, 'player_ids' => []]
                ],
                'rounds' => []
            ]
        ]);
    }

    private function createClub()
    {
        return Club::create([
            'name' => 'Test Club',
            'city' => 'Test City',
            'address' => 'Test Address',
            'representative' => 'Test Rep',
            'email' => 'test@example.com',
            'phone' => '123456',
            'status' => 'Active'
        ]);
    }

    public function test_director_can_view_team_edit_page()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = $this->createTournamentWithTeams($director);

        $response = $this->actingAs($director)->get(route('tournaments.teams.edit', [$tournament, 't1']));

        $response->assertStatus(200);
        $response->assertSee('Edit Team');
        $response->assertSee('Team A');
    }

    public function test_director_can_update_team_name()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = $this->createTournamentWithTeams($director);

        $response = $this->actingAs($director)->patch(route('tournaments.teams.update', [$tournament, 't1']), [
            'name' => 'Updated Team Name',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $tournament->refresh();
        $team = collect($tournament->team_results->teams)->firstWhere('id', 't1');
        
        $this->assertEquals('Updated Team Name', $team->name);
    }

    public function test_director_can_add_player_to_team()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $club = $this->createClub();
        $player = Player::create(['first_name' => 'P1', 'last_name' => 'L1', 'club_id' => $club->id]);
        $tournament = $this->createTournamentWithTeams($director);

        $response = $this->actingAs($director)->post(route('tournaments.teams.players.add', [$tournament, 't1']), [
            'player_id' => $player->id
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $tournament->refresh();
        $this->assertContains($player->id, $tournament->team_results->teams[0]->player_ids);
    }

    public function test_director_cannot_add_same_player_to_two_teams()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $club = $this->createClub();
        $player = Player::create(['first_name' => 'P1', 'last_name' => 'L1', 'club_id' => $club->id]);
        
        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => [
                'teams' => [
                    ['id' => 't1', 'name' => 'Team A', 'captain_id' => 0, 'player_ids' => [$player->id]],
                    ['id' => 't2', 'name' => 'Team B', 'captain_id' => 0, 'player_ids' => []]
                ],
                'rounds' => []
            ]
        ]);

        $response = $this->actingAs($director)->post(route('tournaments.teams.players.add', [$tournament, 't2']), [
            'player_id' => $player->id
        ]);

        $response->assertSessionHasErrors('player_id');
    }

    public function test_director_can_remove_player_from_team()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => [
                'teams' => [
                    ['id' => 't1', 'name' => 'Team A', 'captain_id' => 10, 'player_ids' => [10, 20]]
                ],
                'rounds' => []
            ]
        ]);

        $response = $this->actingAs($director)->delete(route('tournaments.teams.players.remove', [$tournament, 't1', 10]));

        $response->assertSessionHas('success');
        
        $tournament->refresh();
        $team = $tournament->team_results->teams[0];
        $this->assertNotContains(10, $team->player_ids);
        $this->assertEquals(0, $team->captain_id); // Captain was 10, should be unset
    }

    public function test_director_can_set_team_captain()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => [
                'teams' => [
                    ['id' => 't1', 'name' => 'Team A', 'captain_id' => 0, 'player_ids' => [10, 20]]
                ],
                'rounds' => []
            ]
        ]);

        $response = $this->actingAs($director)->post(route('tournaments.teams.captain.set', [$tournament, 't1', 20]));

        $response->assertSessionHas('success');
        
        $tournament->refresh();
        $this->assertEquals(20, $tournament->team_results->teams[0]->captain_id);
    }

    public function test_director_cannot_edit_team_of_others_tournament()
    {
        $director1 = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $director2 = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        
        $tournament = Tournament::factory()->create([
            'user_id' => $director1->id,
            'team_results' => [
                'teams' => [['id' => 't1', 'name' => 'Team A', 'captain_id' => 0, 'player_ids' => []]],
                'rounds' => []
            ]
        ]);

        $response = $this->actingAs($director2)->get(route('tournaments.teams.edit', [$tournament, 't1']));
        $response->assertStatus(403);

        $response = $this->actingAs($director2)->patch(route('tournaments.teams.update', [$tournament, 't1']), [
            'name' => 'Hacked'
        ]);
        $response->assertStatus(403);
    }
}
