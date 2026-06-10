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

    public function test_director_can_add_double_dummy_analysis_to_board()
    {
        config([
            'services.dds_analyzer.command' => [
                PHP_BINARY,
                base_path('tests/Fixtures/dds_analyzer_fake.php'),
            ],
        ]);

        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create(['user_id' => $director->id]);
        $boardSet = BoardSet::create(['tournament_id' => $tournament->id, 'name' => 'DDS Set']);
        $board = Board::create([
            'board_set_id' => $boardSet->id,
            'board_number' => 1,
            'vulnerability' => 'None',
            'cards_north' => ['S' => 'AKQJ', 'H' => 'AKQ', 'D' => 'AKQ', 'C' => 'AKQ'],
            'cards_east' => ['S' => 'T987', 'H' => 'T98', 'D' => 'T98', 'C' => 'T98'],
            'cards_south' => ['S' => '6543', 'H' => '765', 'D' => '765', 'C' => '765'],
            'cards_west' => ['S' => '2', 'H' => 'J432', 'D' => 'J432', 'C' => 'J432'],
        ]);

        $response = $this->actingAs($director)->post(
            route('tournaments.board-sets.boards.double-dummy', [$tournament, $boardSet, $board])
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $board->refresh();
        $this->assertEquals('dds-test', $board->double_dummy_analysis['engine']);
        $this->assertEquals(10, $board->double_dummy_analysis['table']['N']['strains']['S']);
        $this->assertEquals('4S', $board->double_dummy_analysis['best_contract']['contract']);
        $this->assertEquals('4 Spades by North. 420 points.', $board->double_dummy_analysis['best_contract']['description']);

        $this->actingAs($director)
            ->get(route('tournaments.board-sets.show', [$tournament, $boardSet]))
            ->assertSee('Double Dummy Analysis')
            ->assertSee('4 Spades by North. 420 points.')
            ->assertSee('Recalculate Double Dummy');
    }

    public function test_director_can_add_double_dummy_analysis_to_all_boards_in_set()
    {
        config([
            'services.dds_analyzer.command' => [
                PHP_BINARY,
                base_path('tests/Fixtures/dds_analyzer_fake.php'),
            ],
        ]);

        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create(['user_id' => $director->id]);
        $boardSet = BoardSet::create(['tournament_id' => $tournament->id, 'name' => 'DDS Set']);

        foreach ([1, 2] as $boardNumber) {
            Board::create([
                'board_set_id' => $boardSet->id,
                'board_number' => $boardNumber,
                'vulnerability' => 'None',
                'cards_north' => ['S' => 'AKQJ', 'H' => 'AKQ', 'D' => 'AKQ', 'C' => 'AKQ'],
                'cards_east' => ['S' => 'T987', 'H' => 'T98', 'D' => 'T98', 'C' => 'T98'],
                'cards_south' => ['S' => '6543', 'H' => '765', 'D' => '765', 'C' => '765'],
                'cards_west' => ['S' => '2', 'H' => 'J432', 'D' => 'J432', 'C' => 'J432'],
            ]);
        }

        $response = $this->actingAs($director)->post(
            route('tournaments.board-sets.double-dummy', [$tournament, $boardSet])
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(
            2,
            Board::where('board_set_id', $boardSet->id)
                ->whereNotNull('double_dummy_analysis')
                ->count()
        );

        $this->actingAs($director)
            ->get(route('tournaments.board-sets.show', [$tournament, $boardSet]))
            ->assertSee('Analyze All Boards')
            ->assertSee('4 Spades by North. 420 points.');
    }

    public function test_director_can_edit_board_cards()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create(['user_id' => $director->id]);
        $boardSet = BoardSet::create(['tournament_id' => $tournament->id, 'name' => 'Editable Set']);
        $board = Board::create([
            'board_set_id' => $boardSet->id,
            'board_number' => 1,
            'vulnerability' => 'None',
            'cards_north' => ['S' => 'AKQJ', 'H' => 'AKQ', 'D' => 'AKQ', 'C' => 'AKQ'],
            'cards_east' => ['S' => 'T987', 'H' => 'T98', 'D' => 'T98', 'C' => 'T98'],
            'cards_south' => ['S' => '6543', 'H' => '765', 'D' => '765', 'C' => '765'],
            'cards_west' => ['S' => '2', 'H' => 'J432', 'D' => 'J432', 'C' => 'J432'],
            'double_dummy_analysis' => ['engine' => 'old'],
        ]);

        $response = $this->actingAs($director)->patch(
            route('tournaments.board-sets.boards.update', [$tournament, $boardSet, $board]),
            [
                'board_number' => 2,
                'vulnerability' => 'NS',
                'cards' => [
                    'N' => ['S' => 'QJ6', 'H' => 'K652', 'D' => 'J85', 'C' => 'T98'],
                    'E' => ['S' => '873', 'H' => 'J97', 'D' => 'AT764', 'C' => 'Q4'],
                    'S' => ['S' => 'K5', 'H' => 'T83', 'D' => 'KQ9', 'C' => 'A7652'],
                    'W' => ['S' => 'AT942', 'H' => 'AQ4', 'D' => '32', 'C' => 'KJ3'],
                ],
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $board->refresh();
        $this->assertEquals(2, $board->board_number);
        $this->assertEquals('NS', $board->vulnerability);
        $this->assertEquals('QJ6', $board->cards_north['S']);
        $this->assertEquals('A7652', $board->cards_south['C']);
        $this->assertNull($board->double_dummy_analysis);

        $this->actingAs($director)
            ->get(route('tournaments.board-sets.show', [$tournament, $boardSet]))
            ->assertSee('Edit Board')
            ->assertSee('Save Board');
    }

    public function test_board_edit_rejects_duplicate_cards()
    {
        $director = User::factory()->create(['role' => User::ROLE_DIRECTOR]);
        $tournament = Tournament::factory()->create(['user_id' => $director->id]);
        $boardSet = BoardSet::create(['tournament_id' => $tournament->id, 'name' => 'Editable Set']);
        $board = Board::create([
            'board_set_id' => $boardSet->id,
            'board_number' => 1,
            'vulnerability' => 'None',
            'cards_north' => ['S' => 'AKQJ', 'H' => 'AKQ', 'D' => 'AKQ', 'C' => 'AKQ'],
            'cards_east' => ['S' => 'T987', 'H' => 'T98', 'D' => 'T98', 'C' => 'T98'],
            'cards_south' => ['S' => '6543', 'H' => '765', 'D' => '765', 'C' => '765'],
            'cards_west' => ['S' => '2', 'H' => 'J432', 'D' => 'J432', 'C' => 'J432'],
        ]);

        $response = $this->actingAs($director)->patch(
            route('tournaments.board-sets.boards.update', [$tournament, $boardSet, $board]),
            [
                'board_number' => 1,
                'vulnerability' => 'None',
                'cards' => [
                    'N' => ['S' => 'AKQJ', 'H' => 'AKQ', 'D' => 'AKQ', 'C' => 'AKQ'],
                    'E' => ['S' => 'A987', 'H' => 'T98', 'D' => 'T98', 'C' => 'T98'],
                    'S' => ['S' => '6543', 'H' => '765', 'D' => '765', 'C' => '765'],
                    'W' => ['S' => '2', 'H' => 'J432', 'D' => 'J432', 'C' => 'J432'],
                ],
            ]
        );

        $response->assertSessionHasErrors('board_edit');

        $board->refresh();
        $this->assertEquals('T987', $board->cards_east['S']);
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
