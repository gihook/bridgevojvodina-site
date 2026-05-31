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
                        ],
                        'boards' => [
                            [
                                'board_number' => 1,
                                'home_contract' => '3NT', 'home_declarer' => 'S', 'home_lead' => 'D4', 'home_tricks' => 9, 'home_score' => 400,
                                'away_contract' => '3NT', 'away_declarer' => 'N', 'away_lead' => 'S2', 'away_tricks' => 10, 'away_score' => 430,
                                'home_imp' => 0, 'away_imp' => 1
                            ],
                            [
                                'board_number' => 2,
                                'home_contract' => '4S', 'home_declarer' => 'W', 'home_lead' => 'HK', 'home_tricks' => 10, 'home_score' => -420,
                                'away_contract' => '4S', 'away_declarer' => 'E', 'away_lead' => 'H5', 'away_tricks' => 9, 'away_score' => 50,
                                'home_imp' => 10, 'away_imp' => 0
                            ],
                            [
                                'board_number' => 3,
                                'home_contract' => '2H', 'home_declarer' => 'N', 'home_lead' => 'C3', 'home_tricks' => 8, 'home_score' => 110,
                                'away_contract' => '2H', 'away_declarer' => 'S', 'away_lead' => 'C9', 'away_tricks' => 8, 'away_score' => 110,
                                'home_imp' => 0, 'away_imp' => 0
                            ],
                        ],
                        'open_ns_ids' => [6, 85],
                        'open_ew_ids' => [83, 81],
                        'closed_ns_ids' => [80, 84],
                        'closed_ew_ids' => [73, 55],
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

        $tournament = Tournament::create([
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

        // Create BoardSet and Boards
        $boardSet = \App\Models\BoardSet::create([
            'tournament_id' => $tournament->id,
            'name' => 'Bordovi za Timsko prvenstvo NS',
        ]);

        // Link BoardSet to rounds in JSON
        $results = $tournament->team_results;
        foreach ($results->rounds as $round) {
            $round->board_set_id = $boardSet->id;
        }
        $tournament->team_results = $results;
        $tournament->save();

        // Seed some boards
        $boardData = [
            1 => [
                'vul' => 'None',
                'n' => ['S' => 'AKJ', 'H' => 'Q9', 'D' => 'J109', 'C' => 'KT98'],
                's' => ['S' => '76', 'H' => 'AK', 'D' => 'AK876', 'C' => 'J765'],
                'e' => ['S' => 'QT9', 'H' => 'T876', 'D' => '543', 'C' => 'Q32'],
                'w' => ['S' => '85432', 'H' => 'J5432', 'D' => 'Q2', 'C' => 'A'],
            ],
            2 => [
                'vul' => 'NS',
                'n' => ['S' => '7', 'H' => 'AKJT9', 'D' => 'Q876', 'C' => 'A32'],
                's' => ['S' => 'KJT98', 'H' => 'Q2', 'D' => '543', 'C' => '876'],
                'e' => ['S' => 'A6543', 'H' => '876', 'D' => 'KJT', 'C' => 'QJ'],
                'w' => ['S' => 'Q2', 'H' => '543', 'D' => 'A92', 'C' => 'KT954'],
            ],
            3 => [
                'vul' => 'EW',
                'n' => ['S' => 'A65', 'H' => 'AK6', 'D' => 'QT9', 'C' => 'JT98'],
                's' => ['S' => 'KJT', 'H' => 'Q754', 'D' => '876', 'C' => 'K76'],
                'e' => ['S' => 'Q9432', 'H' => 'J32', 'D' => '543', 'C' => 'A2'],
                'w' => ['S' => '87', 'H' => 'T98', 'D' => 'AKJ2', 'C' => 'Q543'],
            ],
            4 => [
                'vul' => 'All',
                'n' => ['S' => 'AKQ', 'H' => 'JT9', 'D' => '876', 'C' => '5432'],
                's' => ['S' => 'JT9', 'H' => 'A', 'D' => 'AKQJT', 'C' => 'AKQJ'],
                'e' => ['S' => '8765', 'H' => 'KQ7', 'D' => '543', 'C' => 'T98'],
                'w' => ['S' => '432', 'H' => '865432', 'D' => '92', 'C' => '76'],
            ],
            5 => [
                'vul' => 'NS',
                'n' => ['S' => 'J98', 'H' => 'A5', 'D' => 'QT98', 'C' => 'KT98'],
                's' => ['S' => 'A5', 'H' => 'KJ87', 'D' => '6543', 'C' => 'Q42'],
                'e' => ['S' => 'KQT', 'H' => 'T9432', 'D' => 'AJ', 'C' => 'J53'],
                'w' => ['S' => '76432', 'H' => 'Q6', 'D' => 'K72', 'C' => 'A76'],
            ],
            6 => [
                'vul' => 'EW',
                'n' => ['S' => 'A65', 'H' => 'QJT', 'D' => 'A765', 'C' => '987'],
                's' => ['S' => 'QJ', 'H' => 'A987', 'D' => 'KJ8', 'C' => 'KJ65'],
                'e' => ['S' => 'KT987', 'H' => 'K54', 'D' => 'QT9', 'C' => 'T2'],
                'w' => ['S' => '432', 'H' => '632', 'D' => '432', 'C' => 'AQ43'],
            ],
            7 => [
                'vul' => 'All',
                'n' => ['S' => 'AKJT9', 'H' => '2', 'D' => 'AKQ', 'C' => 'KJ65'],
                's' => ['S' => 'Q', 'H' => 'AKQT9', 'D' => 'JT9', 'C' => 'AQT9'],
                'e' => ['S' => '876', 'H' => '876', 'D' => '876', 'C' => '8765'],
                'w' => ['S' => '5432', 'H' => 'J543', 'D' => '5432', 'C' => '4'],
            ],
            8 => [
                'vul' => 'None',
                'n' => ['S' => 'A', 'H' => 'AKQJT', 'D' => 'AKQJT', 'C' => 'AK'],
                's' => ['S' => 'JT987', 'H' => '987', 'D' => '987', 'C' => '98'],
                'e' => ['S' => '65432', 'H' => '65432', 'D' => '654', 'C' => '7'],
                'w' => ['S' => 'KQ', 'H' => '-', 'D' => '32', 'C' => 'QTJ65432'],
            ],
            9 => [
                'vul' => 'EW',
                'n' => ['S' => 'KQT9', 'H' => 'A54', 'D' => 'J987', 'C' => '42'],
                's' => ['S' => 'A543', 'H' => 'T98', 'D' => 'Q', 'C' => 'AKJT9'],
                'e' => ['S' => 'J2', 'H' => 'K76', 'D' => 'AK654', 'C' => '876'],
                'w' => ['S' => '876', 'H' => 'QJ32', 'D' => 'T32', 'C' => 'Q53'],
            ],
            10 => [
                'vul' => 'All',
                'n' => ['S' => '9876', 'H' => '9876', 'D' => '987', 'C' => '98'],
                's' => ['S' => 'AKQ', 'H' => 'AKQ', 'D' => 'AKQ', 'C' => 'AKQJ'],
                'e' => ['S' => 'JT543', 'H' => 'JT543', 'D' => 'JT', 'C' => 'T'],
                'w' => ['S' => '2', 'H' => '2', 'D' => '65432', 'C' => '765432'],
            ],
            11 => [
                'vul' => 'None',
                'n' => ['S' => 'A65', 'H' => 'QJT', 'D' => 'A765', 'C' => '987'],
                's' => ['S' => 'QJ', 'H' => 'A987', 'D' => 'KJ8', 'C' => 'KJ65'],
                'e' => ['S' => 'KT987', 'H' => 'K54', 'D' => 'QT9', 'C' => 'T2'],
                'w' => ['S' => '432', 'H' => '632', 'D' => '432', 'C' => 'AQ43'],
            ],
            12 => [
                'vul' => 'NS',
                'n' => ['S' => '7', 'H' => 'AKJT9', 'D' => 'Q876', 'C' => 'A32'],
                's' => ['S' => 'KJT98', 'H' => 'Q2', 'D' => '543', 'C' => '876'],
                'e' => ['S' => 'A6543', 'H' => '876', 'D' => 'KJT', 'C' => 'QJ'],
                'w' => ['S' => 'Q2', 'H' => '543', 'D' => 'A92', 'C' => 'KT954'],
            ],
        ];

        foreach ($boardData as $num => $data) {
            \App\Models\Board::create([
                'board_set_id' => $boardSet->id,
                'board_number' => $num,
                'vulnerability' => $data['vul'],
                'cards_north' => $data['n'],
                'cards_south' => $data['s'],
                'cards_east' => $data['e'],
                'cards_west' => $data['w'],
            ]);
        }
    }
}
