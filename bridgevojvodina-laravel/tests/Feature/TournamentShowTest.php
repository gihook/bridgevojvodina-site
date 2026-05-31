<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_tournament_show_page_displays_results()
    {
        $user = User::factory()->create();
        $club = Club::create([
            'name' => 'NSBK', 'city' => 'NS', 'address' => 'A1', 'representative' => 'R1', 'email' => 'e@e.com', 'phone' => '1', 'status' => 'Active'
        ]);
        $player = Player::create(['first_name' => 'Slobodan', 'last_name' => 'Guzvica', 'club_id' => $club->id]);

        $tournament = Tournament::create([
            'title' => 'NS Team Cup',
            'description' => 'Desc',
            'details' => 'Details',
            'user_id' => $user->id,
            'team_results' => [
                'teams' => [
                    ['id' => 't1', 'name' => 'Team A', 'captain_id' => $player->id, 'player_ids' => [$player->id], 'total_vp' => 20.5]
                ],
                'rounds' => [
                    [
                        'id' => 'r1', 'name' => 'Match 1',
                        'matches' => [
                            [
                                'home_team_id' => 't1', 'away_team_id' => null,
                                'home_imp' => 10, 'away_imp' => 0, 'home_vp' => 12.0, 'away_vp' => 0.0,
                                'home_lineup' => [['player_id' => $player->id, 'butler_score' => 1.5]]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $response = $this->get("/tournaments/{$tournament->id}");

        $response->assertStatus(200);
        $response->assertSee('Team A');
        $response->assertSee('20.50');
        $response->assertSee('Match 1');
        $response->assertSee('bye');
    }
}
