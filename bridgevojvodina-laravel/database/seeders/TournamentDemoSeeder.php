<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TournamentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();
        $club = Club::first() ?? Club::create([
            'name' => 'NSBK',
            'city' => 'Novi Sad',
            'address' => 'Test 1',
            'representative' => 'Test Rep',
            'email' => 'nsbk@nsbk.com',
            'phone' => '123',
            'status' => 'Active'
        ]);

        $p1 = Player::create(['first_name' => 'Slobodan', 'last_name' => 'Gužvica', 'club_id' => $club->id]);
        $p2 = Player::create(['first_name' => 'Vuk', 'last_name' => 'Trnavac', 'club_id' => $club->id]);
        $p3 = Player::create(['first_name' => 'Jovan', 'last_name' => 'Poljački', 'club_id' => $club->id]);
        $p4 = Player::create(['first_name' => 'Miloš', 'last_name' => 'Vlaškalić', 'club_id' => $club->id]);

        $p5 = Player::create(['first_name' => 'Đuro', 'last_name' => 'Opačić', 'club_id' => $club->id]);
        $p6 = Player::create(['first_name' => 'Stevan', 'last_name' => 'Miškov', 'club_id' => $club->id]);

        $results = [
            'teams' => [
                [
                    'id' => 'team_nsbk',
                    'name' => 'NSBK',
                    'captain_id' => $p1->id,
                    'player_ids' => [$p1->id, $p2->id, $p3->id, $p4->id],
                    'total_vp' => 26.35
                ],
                [
                    'id' => 'team_saturn',
                    'name' => 'SATURN',
                    'captain_id' => $p5->id,
                    'player_ids' => [$p5->id, $p6->id],
                    'total_vp' => 17.78
                ]
            ],
            'rounds' => [
                [
                    'id' => 'round_1',
                    'name' => 'Meč 1',
                    'matches' => [
                        [
                            'home_team_id' => 'team_nsbk',
                            'away_team_id' => 'team_saturn',
                            'home_imp' => 42,
                            'away_imp' => 31,
                            'home_vp' => 13.45,
                            'away_vp' => 6.55,
                            'home_lineup' => [
                                ['player_id' => $p1->id, 'butler_score' => 1.5],
                                ['player_id' => $p2->id, 'butler_score' => 1.2],
                            ],
                            'away_lineup' => [
                                ['player_id' => $p5->id, 'butler_score' => -0.8],
                                ['player_id' => $p6->id, 'butler_score' => -0.5],
                            ]
                        ]
                    ]
                ]
            ]
        ];

        Tournament::create([
            'id' => Str::uuid(),
            'title' => 'Timsko prvenstvo Novog Sada',
            'description' => 'Demo tournament with results',
            'details' => 'Markdown details here',
            'user_id' => $user->id,
            'team_results' => $results
        ]);
    }
}
