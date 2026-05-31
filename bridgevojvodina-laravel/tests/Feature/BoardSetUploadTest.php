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

        $pbnContent = <<<EOD
[Event "Test PBN Event"]
[Board "1"]
[Dealer "N"]
[Vulnerable "None"]
[Deal "N:AKQJ.AKQJ.AKQJ.AK 2.2.2.2 3.3.3.3 4.4.4.4"]
EOD;

        $file = UploadedFile::fake()->createWithContent('test.pbn', $pbnContent);

        $response = $this->actingAs($director)->post(route('tournaments.board-sets.upload', $tournament), [
            'round_id' => 'round1',
            'board_set_file' => $file,
        ]);

        $boardSet = BoardSet::where('name', 'Test PBN Event')->first();
        $response->assertRedirect(route('tournaments.edit', $tournament));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('board_sets', [
            'tournament_id' => $tournament->id,
            'name' => 'Test PBN Event',
        ]);

        $this->assertDatabaseHas('boards', [
            'board_set_id' => $boardSet->id,
            'board_number' => 1,
        ]);

        $board = Board::where('board_set_id', $boardSet->id)->first();
        $this->assertEquals('AKQJ', $board->cards_north['S']);
        $this->assertEquals('2', $board->cards_east['S']);
        $this->assertEquals('3', $board->cards_south['S']);
        $this->assertEquals('4', $board->cards_west['S']);

        $tournament->refresh();
        $this->assertEquals($boardSet->id, $tournament->team_results->rounds[0]->board_set_id);
    }

    public function test_upload_requires_valid_pbn()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => ['rounds' => [['id' => 'r1', 'name' => 'R1', 'matches' => [], 'board_set_id' => null]], 'teams' => [['id' => 't1', 'name' => 'T1', 'captain_id' => 1, 'player_ids' => []]]],
        ]);

        $file = UploadedFile::fake()->createWithContent('bad.pbn', 'not pbn');

        $response = $this->actingAs($director)->post(route('tournaments.board-sets.upload', $tournament), [
            'round_id' => 'r1',
            'board_set_file' => $file,
        ]);

        $response->assertSessionHasErrors('board_set_file');
    }

    public function test_board_set_page_shows_board_preview()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create(['user_id' => $director->id]);
        $boardSet = BoardSet::create(['tournament_id' => $tournament->id, 'name' => 'Preview Set']);
        $board = Board::create([
            'board_set_id' => $boardSet->id,
            'board_number' => 1,
            'vulnerability' => 'None',
            'cards_north' => ['S' => 'AKQJ', 'H' => '', 'D' => '', 'C' => ''],
            'cards_south' => ['S' => '32', 'H' => '', 'D' => '', 'C' => ''],
            'cards_east' => ['S' => '54', 'H' => '', 'D' => '', 'C' => ''],
            'cards_west' => ['S' => '76', 'H' => '', 'D' => '', 'C' => ''],
        ]);

        $response = $this->actingAs($director)->get(route('tournaments.board-sets.show', [$tournament, $boardSet]));

        $response->assertStatus(200);
        $response->assertSee('Preview Set');
        $response->assertSee('AKQJ');
        $response->assertSee('Board');
    }

    public function test_director_can_delete_board_set()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create([
            'user_id' => $director->id,
            'team_results' => [
                'rounds' => [
                    ['id' => 'r1', 'name' => 'Round 1', 'board_set_id' => 1]
                ],
                'teams' => []
            ]
        ]);
        
        $boardSet = BoardSet::create([
            'id' => 1,
            'tournament_id' => $tournament->id,
            'name' => 'To be deleted'
        ]);

        $response = $this->actingAs($director)->delete(route('tournaments.board-sets.destroy', [$tournament, $boardSet]));

        $response->assertRedirect(route('tournaments.edit', $tournament));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('board_sets', ['id' => 1]);
        
        $tournament->refresh();
        $this->assertNull($tournament->team_results->rounds[0]->board_set_id);
    }
}
