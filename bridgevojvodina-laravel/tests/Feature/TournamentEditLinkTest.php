<?php

namespace Tests\Feature;

use App\Models\TournamentConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentEditLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_edit_link_on_draft_show_page()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $otherAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        
        $draft = TournamentConfiguration::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'title' => 'Admin Draft',
            'user_id' => $otherAdmin->id,
            'team_results' => ['teams' => [], 'rounds' => []],
        ]);

        $response = $this->actingAs($admin)->get(route('tournaments.show', $draft->id));

        $response->assertStatus(200);
        $response->assertSee(route('tournaments.edit', $draft->id));
        $response->assertSee(__('Edit Tournament'));
    }

    public function test_director_can_see_edit_link_on_their_own_draft_show_page()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        
        $draft = TournamentConfiguration::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'title' => 'My Draft',
            'user_id' => $director->id,
            'team_results' => ['teams' => [], 'rounds' => []],
        ]);

        $response = $this->actingAs($director)->get(route('tournaments.show', $draft->id));

        $response->assertStatus(200);
        $response->assertSee(route('tournaments.edit', $draft->id));
        $response->assertSee(__('Edit Tournament'));
    }

    public function test_director_cannot_see_edit_link_on_others_published_tournament()
    {
        $director1 = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $director2 = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        
        $tournament = \App\Models\Tournament::factory()->create([
            'user_id' => $director1->id,
            'title' => 'Tournament by D1'
        ]);

        $response = $this->actingAs($director2)->get(route('tournaments.show', $tournament->id));

        $response->assertStatus(200);
        $response->assertDontSee(route('tournaments.edit', $tournament->id));
        $response->assertDontSee(__('Edit Tournament'));
    }
}
