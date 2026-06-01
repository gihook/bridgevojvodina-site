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

        $config = \App\Models\TournamentConfiguration::where('title', 'New Tournament')->first();
        $this->assertNotNull($config);
        $response->assertRedirect(route('tournaments.edit', $config->id));
        $this->assertEquals($director->id, $config->user_id);
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

    public function test_admin_sees_each_tournament_only_once_even_if_published()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $uuid = (string) \Illuminate\Support\Str::uuid();

        // Create a configuration
        $config = new \App\Models\TournamentConfiguration();
        $config->id = $uuid;
        $config->title = 'Published Tournament';
        $config->user_id = $admin->id;
        $config->team_results = ['teams' => [], 'rounds' => []];
        $config->save();

        // Publish it (creates a Tournament record with same ID)
        $tournament = new Tournament();
        $tournament->id = $uuid;
        $tournament->title = 'Published Tournament';
        $tournament->description = 'Published description';
        $tournament->details = 'Published details';
        $tournament->user_id = $admin->id;
        $tournament->team_results = ['teams' => [], 'rounds' => []];
        $tournament->save();

        // Create another configuration that is NOT published
        $draft = new \App\Models\TournamentConfiguration();
        $draft->id = (string) \Illuminate\Support\Str::uuid();
        $draft->title = 'Real Draft';
        $draft->user_id = $admin->id;
        $draft->team_results = ['teams' => [], 'rounds' => []];
        $draft->save();

        $response = $this->actingAs($admin)->get(route('tournaments.index'));

        $response->assertStatus(200);
        
        $content = $response->getContent();
        
        // Should see two tournament entries in total
        $this->assertEquals(2, substr_count($content, '<h3 class="text-lg font-bold">'), "Should see two tournament entries (one published, one draft)");
        $response->assertSee('Published Tournament');
        $response->assertSee('Real Draft');
        
        // Verify only one is marked as Draft
        $response->assertSee('Draft');
        // Count occurrences of the draft badge class
        $this->assertEquals(1, substr_count($content, 'bg-yellow-100 text-yellow-800'), "Should see only one 'Draft' badge");
    }

    public function test_deleting_tournament_removes_it_from_both_tables()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $uuid = (string) \Illuminate\Support\Str::uuid();

        // Create a configuration
        $config = new \App\Models\TournamentConfiguration();
        $config->id = $uuid;
        $config->title = 'To Be Deleted';
        $config->user_id = $admin->id;
        $config->team_results = ['teams' => [], 'rounds' => []];
        $config->save();

        // Publish it (creates a Tournament record with same ID)
        $tournament = new Tournament();
        $tournament->id = $uuid;
        $tournament->title = 'To Be Deleted';
        $tournament->description = 'Published';
        $tournament->details = 'Details';
        $tournament->user_id = $admin->id;
        $tournament->team_results = ['teams' => [], 'rounds' => []];
        $tournament->save();

        $this->assertDatabaseHas('tournament_configurations', ['id' => $uuid]);
        $this->assertDatabaseHas('tournaments', ['id' => $uuid]);

        $response = $this->actingAs($admin)->delete(route('tournaments.destroy', $uuid));

        $response->assertRedirect(route('tournaments.index'));
        $this->assertDatabaseMissing('tournament_configurations', ['id' => $uuid]);
        $this->assertDatabaseMissing('tournaments', ['id' => $uuid]);
    }

    public function test_deleting_unpublished_draft_works()
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $uuid = (string) \Illuminate\Support\Str::uuid();

        $config = new \App\Models\TournamentConfiguration();
        $config->id = $uuid;
        $config->title = 'Draft Only';
        $config->user_id = $admin->id;
        $config->team_results = ['teams' => [], 'rounds' => []];
        $config->save();

        $this->assertDatabaseHas('tournament_configurations', ['id' => $uuid]);
        $this->assertDatabaseMissing('tournaments', ['id' => $uuid]);

        $response = $this->actingAs($admin)->delete(route('tournaments.destroy', $uuid));

        $response->assertRedirect(route('tournaments.index'));
        $this->assertDatabaseMissing('tournament_configurations', ['id' => $uuid]);
    }

    public function test_tournaments_index_shows_empty_message()
    {
        // Ensure no tournaments exist (RefreshDatabase handles this, but let's be sure)
        \App\Models\Tournament::query()->delete();
        \App\Models\TournamentConfiguration::query()->delete();

        $response = $this->get(route('tournaments.index'));

        $response->assertStatus(200);
        $response->assertSee(__('No tournaments found.'));
    }

    public function test_standings_only_sum_vps_for_completed_rounds()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        
        $teamResults = [
            'teams' => [
                ['id' => 't1', 'name' => 'Team 1', 'number' => 1, 'total_vp' => 0, 'captain_id' => 0, 'player_ids' => []],
                ['id' => 't2', 'name' => 'Team 2', 'number' => 2, 'total_vp' => 0, 'captain_id' => 0, 'player_ids' => []],
            ],
            'rounds' => [
                [
                    'id' => 'r1', 'name' => 'Round 1', 'status' => 'complete', 'boards_per_round' => 16,
                    'matches' => [
                        [
                            'id' => 'm1', 'home_team_id' => 't1', 'away_team_id' => 't2', 
                            'home_imp' => 40, 'away_imp' => 0, 'home_vp' => 18.09, 'away_vp' => 1.91,
                            'boards' => []
                        ]
                    ]
                ],
                [
                    'id' => 'r2', 'name' => 'Round 2', 'status' => 'inProgress', 'boards_per_round' => 16,
                    'matches' => [
                        [
                            'id' => 'm2', 'home_team_id' => 't1', 'away_team_id' => 't2', 
                            'home_imp' => 50, 'away_imp' => 0, 'home_vp' => 19.16, 'away_vp' => 0.84,
                            'boards' => []
                        ]
                    ]
                ]
            ],
            'bye_vp' => 12.0,
            'boards_per_round' => 16
        ];

        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => $teamResults,
        ]);

        // Trigger recalculateStandings by updating settings
        $this->actingAs($director)->patch(route('tournaments.settings.update', $tournament), [
            'bye_vp' => 12.0,
            'boards_per_round' => 16,
        ]);

        $tournament->refresh();
        $teams = collect($tournament->team_results->teams)->keyBy('id');
        
        $this->assertEquals(18.09, $teams['t1']->total_vp, 'Team 1 VP should only include completed rounds');
        $this->assertEquals(1.91, $teams['t2']->total_vp, 'Team 2 VP should only include completed rounds');

        // Now mark Round 2 as complete
        $this->actingAs($director)->patch(route('tournaments.rounds.status.update', [$tournament, 'r2']), [
            'status' => 'complete',
        ]);

        $tournament->refresh();
        $teams = collect($tournament->team_results->teams)->keyBy('id');

        // Now both rounds should be summed
        $this->assertEquals(37.25, $teams['t1']->total_vp, 'Team 1 VP should include both rounds after R2 is complete');
        $this->assertEquals(2.75, $teams['t2']->total_vp, 'Team 2 VP should include both rounds after R2 is complete');
    }
}
