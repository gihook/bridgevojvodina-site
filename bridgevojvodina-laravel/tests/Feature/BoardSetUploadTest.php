<?php

namespace Tests\Feature;

use App\Models\Tournament;
use App\Models\User;
use App\Models\BoardSet;
use App\Models\Board;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BoardSetUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_director_can_upload_board_set()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        
        $teamResults = [
            'teams' => [
                ['id' => 'team1', 'name' => 'Team 1', 'captain_id' => 1, 'player_ids' => [1, 2]]
            ],
            'rounds' => [
                ['id' => 'round1', 'name' => 'Round 1', 'matches' => []]
            ]
        ];

        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => $teamResults,
        ]);

        $boardSetData = [
            'Name' => 'Test Board Set',
            'Boards' => [
                [
                    'BoardNumber' => 1,
                    'Hands' => [
                        [
                            'Seat' => 'North',
                            'Cards' => [
                                ['Suit' => 'Spades', 'Rank' => 'Ace'],
                                ['Suit' => 'Spades', 'Rank' => 'King'],
                                ['Suit' => 'Hearts', 'Rank' => 'Queen'],
                            ]
                        ],
                        [
                            'Seat' => 'South',
                            'Cards' => [
                                ['Suit' => 'Diamonds', 'Rank' => 'Jack'],
                                ['Suit' => 'Clubs', 'Rank' => 'Ten'],
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $file = UploadedFile::fake()->createWithContent('board_set.json', json_encode($boardSetData));

        $response = $this->actingAs($director)->post(route('tournaments.board-sets.upload', $tournament), [
            'round_id' => 'round1',
            'board_set_json' => $file,
        ]);

        $response->assertRedirect(route('tournaments.edit', $tournament));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('board_sets', [
            'tournament_id' => $tournament->id,
            'name' => 'Test Board Set',
        ]);

        $boardSet = BoardSet::where('name', 'Test Board Set')->first();
        $this->assertDatabaseHas('boards', [
            'board_set_id' => $boardSet->id,
            'board_number' => 1,
            'vulnerability' => 'None',
        ]);

        $board = Board::where('board_set_id', $boardSet->id)->first();
        $this->assertEquals('AK', $board->cards_north['S']);
        $this->assertEquals('Q', $board->cards_north['H']);
        $this->assertEquals('J', $board->cards_south['D']);
        $this->assertEquals('T', $board->cards_south['C']);

        $tournament->refresh();
        $this->assertEquals($boardSet->id, $tournament->team_results->rounds[0]->board_set_id);
    }

    public function test_upload_requires_valid_json()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => ['rounds' => [['id' => 'r1', 'name' => 'R1']]],
        ]);

        $file = UploadedFile::fake()->createWithContent('bad.json', 'not json');

        $response = $this->actingAs($director)->post(route('tournaments.board-sets.upload', $tournament), [
            'round_id' => 'r1',
            'board_set_json' => $file,
        ]);

        $response->assertSessionHasErrors('board_set_json');
    }
}
