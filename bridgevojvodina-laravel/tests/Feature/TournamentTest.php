<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_tournaments_index()
    {
        Tournament::factory()->create([
            'title' => 'Test Tournament',
            'user_id' => User::factory()->create(['role' => User::ROLE_ADMIN])->id,
        ]);

        $response = $this->get(route('tournaments.index'));

        $response->assertStatus(200);
        $response->assertSee('Test Tournament');
    }

    public function test_guests_can_view_tournament_show()
    {
        $tournament = Tournament::factory()->create([
            'title' => 'Detailed Tournament',
            'details' => '# Markdown Title',
            'user_id' => User::factory()->create(['role' => User::ROLE_ADMIN])->id,
        ]);

        $response = $this->get(route('tournaments.show', $tournament));

        $response->assertStatus(200);
        $response->assertSee('Detailed Tournament');
        $response->assertSee('<h1>Markdown Title</h1>', false);
    }

    public function test_directors_can_create_tournaments()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);

        $response = $this->actingAs($director)->post(route('tournaments.store'), [
            'title' => 'New Tournament',
            'description' => 'Some description',
            'details' => 'Some details',
        ]);

        $response->assertRedirect(route('tournaments.index'));
        $this->assertDatabaseHas('tournaments', ['title' => 'New Tournament', 'user_id' => $director->id]);
    }

    public function test_directors_can_edit_their_own_tournaments()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create(['user_id' => $director->id]);

        $response = $this->actingAs($director)->get(route('tournaments.edit', $tournament));
        $response->assertStatus(200);

        $response = $this->actingAs($director)->patch(route('tournaments.update', $tournament), [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'details' => 'Updated details',
        ]);

        $response->assertRedirect(route('tournaments.index'));
        $this->assertDatabaseHas('tournaments', ['id' => $tournament->id, 'title' => 'Updated Title']);
    }

    public function test_directors_cannot_edit_others_tournaments()
    {
        $director1 = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $director2 = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create(['user_id' => $director1->id]);

        $response = $this->actingAs($director2)->get(route('tournaments.edit', $tournament));
        $response->assertStatus(403);

        $response = $this->actingAs($director2)->patch(route('tournaments.update', $tournament), [
            'title' => 'Hacked Title',
        ]);
        $response->assertStatus(403);
    }

    public function test_admins_can_edit_any_tournament()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create(['user_id' => $director->id]);

        $response = $this->actingAs($admin)->get(route('tournaments.edit', $tournament));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->patch(route('tournaments.update', $tournament), [
            'title' => 'Admin Updated',
            'description' => 'Desc',
            'details' => 'Details',
        ]);

        $response->assertRedirect(route('tournaments.index'));
        $this->assertDatabaseHas('tournaments', ['id' => $tournament->id, 'title' => 'Admin Updated']);
    }

    public function test_player_cannot_create_tournament()
    {
        $player = User::factory()->create(['role' => User::ROLE_PLAYER]);

        $response = $this->actingAs($player)->post(route('tournaments.store'), [
            'title' => 'Illegal Tournament',
        ]);

        $response->assertStatus(403);
    }

    public function test_director_cannot_edit_others_tournament()
    {
        $director1 = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $director2 = User::factory()->create(['role' => User::ROLE_DIRECTOR]);

        $tournament = Tournament::factory()->create(['user_id' => $director1->id]);

        $response = $this->actingAs($director2)->get(route('tournaments.edit', $tournament));
        $response->assertStatus(403);

        $response = $this->actingAs($director2)->patch(route('tournaments.update', $tournament), [
            'title' => 'Hijacked',
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_edit_any_tournament()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);

        $tournament = Tournament::factory()->create(['user_id' => $director->id]);

        $response = $this->actingAs($admin)->get(route('tournaments.edit', $tournament));
        $response->assertStatus(200);

        $response = $this->actingAs($admin)->patch(route('tournaments.update', $tournament), [
            'title' => 'Admin Edited',
            'description' => 'Desc',
            'details' => 'Details',
        ]);
        $response->assertRedirect();
        $this->assertEquals('Admin Edited', $tournament->fresh()->title);
        }

        public function test_director_can_update_round_status()
        {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => [
                'rounds' => [
                    ['id' => 'r1', 'name' => 'Round 1', 'status' => 'idle']
                ],
                'teams' => []
            ]
        ]);

        $response = $this->actingAs($director)->patch(route('tournaments.rounds.status.update', [$tournament, 'r1']), [
            'status' => 'inProgress',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $tournament->refresh();
        $this->assertEquals('inProgress', $tournament->team_results->rounds[0]->status);
        }
        }
