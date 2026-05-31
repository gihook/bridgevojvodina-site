<?php

namespace Database\Seeders;

use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TournamentDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'admin@bridgevojvodina.rs')->first() ?? User::factory()->create();

        $teams = [
            [
                'id' => 'team_nsbk',
                'name' => 'NSBK',
                'captain_id' => 30,
                'player_ids' => [30, 50, 24, 27, 77, 78, 79],
                'total_vp' => 26.35
            ],
            [
                'id' => 'team_saturn',
                'name' => 'Saturn',
                'captain_id' => 3,
                'player_ids' => [3, 2, 29, 57, 38],
                'total_vp' => 17.78
            ],
            [
                'id' => 'team_ekspert_1',
                'name' => 'BK Ekspert 1',
                'captain_id' => 6,
                'player_ids' => [6, 73, 55, 85, 68],
                'total_vp' => 21.80
            ],
            [
                'id' => 'team_ekspert_2',
                'name' => 'BK Ekspert 2',
                'captain_id' => 19,
                'player_ids' => [19, 60, 80, 81, 82, 83, 84],
                'total_vp' => 17.30
            ],
            [
                'id' => 'team_ns1',
                'name' => 'NS1',
                'captain_id' => 4,
                'player_ids' => [4, 31, 54, 16, 69, 86],
                'total_vp' => 20.77
            ],
        ];

        $rounds = [
            [
                'id' => 'round_1',
                'name' => '1. KOLO',
                'matches' => [
                    [
                        'home_team_id' => 'team_ekspert_1',
                        'away_team_id' => 'team_ekspert_2',
                        'home_imp' => 36,
                        'away_imp' => 21,
                        'home_vp' => 14.70,
                        'away_vp' => 5.30,
                        'home_lineup' => [
                            ['player_id' => 6], ['player_id' => 85], ['player_id' => 73], ['player_id' => 55]
                        ],
                        'away_lineup' => [
                            ['player_id' => 83], ['player_id' => 81], ['player_id' => 80], ['player_id' => 84], ['player_id' => 19]
                        ]
                    ],
                    [
                        'home_team_id' => 'team_ns1',
                        'away_team_id' => 'team_nsbk',
                        'home_imp' => 31,
                        'away_imp' => 42,
                        'home_vp' => 6.55,
                        'away_vp' => 13.45,
                        'home_lineup' => [
                            ['player_id' => 31], ['player_id' => 60], ['player_id' => 16], ['player_id' => 54]
                        ],
                        'away_lineup' => [
                            ['player_id' => 78], ['player_id' => 50], ['player_id' => 27], ['player_id' => 24]
                        ]
                    ],
                    [
                        'home_team_id' => 'team_saturn',
                        'away_team_id' => 'bye',
                        'home_vp' => 12.00,
                        'away_vp' => 0.00
                    ]
                ]
            ],
            [
                'id' => 'round_2',
                'name' => '2. KOLO',
                'matches' => [
                    [
                        'home_team_id' => 'team_saturn',
                        'away_team_id' => 'team_ns1',
                        'home_imp' => 16,
                        'away_imp' => 30,
                        'home_vp' => 5.78,
                        'away_vp' => 14.22,
                        'home_lineup' => [
                            ['player_id' => 57], ['player_id' => 38], ['player_id' => 2], ['player_id' => 3]
                        ],
                        'away_lineup' => [
                            ['player_id' => 31], ['player_id' => 54], ['player_id' => 86], ['player_id' => 16]
                        ]
                    ],
                    [
                        'home_team_id' => 'team_ekspert_1',
                        'away_team_id' => 'team_nsbk',
                        'home_imp' => 0,
                        'away_imp' => 9,
                        'home_vp' => 7.10,
                        'away_vp' => 12.90,
                        'home_lineup' => [
                            ['player_id' => 6], ['player_id' => 68], ['player_id' => 73], ['player_id' => 55]
                        ],
                        'away_lineup' => [
                            ['player_id' => 78], ['player_id' => 30], ['player_id' => 27], ['player_id' => 24]
                        ]
                    ],
                    [
                        'home_team_id' => 'team_ekspert_2',
                        'away_team_id' => 'bye',
                        'home_vp' => 12.00,
                        'away_vp' => 0.00
                    ]
                ]
            ],
            [
                'id' => 'round_3',
                'name' => '3. KOLO',
                'matches' => [
                    ['home_team_id' => 'team_ekspert_1', 'away_team_id' => 'team_saturn'],
                    ['home_team_id' => 'team_ekspert_2', 'away_team_id' => 'team_nsbk'],
                    ['home_team_id' => 'team_ns1', 'away_team_id' => 'bye', 'home_vp' => 12.00]
                ]
            ],
            [
                'id' => 'round_4',
                'name' => '4. KOLO',
                'matches' => [
                    ['home_team_id' => 'team_ns1', 'away_team_id' => 'team_ekspert_2'],
                    ['home_team_id' => 'team_nsbk', 'away_team_id' => 'team_saturn'],
                    ['home_team_id' => 'team_ekspert_1', 'away_team_id' => 'bye', 'home_vp' => 12.00]
                ]
            ],
            [
                'id' => 'round_5',
                'name' => '5. KOLO',
                'matches' => [
                    ['home_team_id' => 'team_ekspert_2', 'away_team_id' => 'team_saturn'],
                    ['home_team_id' => 'team_ns1', 'away_team_id' => 'team_ekspert_1'],
                    ['home_team_id' => 'team_nsbk', 'away_team_id' => 'bye', 'home_vp' => 12.00]
                ]
            ]
        ];

        Tournament::create([
            'id' => Str::uuid(),
            'title' => 'Timsko prvenstvo Novog Sada',
            'description' => 'Zvanično timsko prvenstvo Novog Sada za sezonu 2026.',
            'details' => "## Format takmičenja\n- Round-robin sistem (svako sa svakim)\n- Obračun po VP skali za 12 bordova\n- Dozvoljeno do 7 igrača po timu",
            'user_id' => $user->id,
            'team_results' => [
                'teams' => $teams,
                'rounds' => $rounds
            ],
            'is_completed' => false
        ]);
    }
}
