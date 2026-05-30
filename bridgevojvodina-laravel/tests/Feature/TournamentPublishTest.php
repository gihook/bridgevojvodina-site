<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardSet;
use App\Models\Player;
use App\Models\RunningTournament;
use App\Models\Tournament;
use App\Models\User;
use App\Models\Club;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TournamentPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_publish_running_tournament()
    {
        $user = User::factory()->create();
        $club = Club::create([
            'name' => 'Test Club',
            'city' => 'Novi Sad',
            'address' => 'Test 1',
            'representative' => 'Test Rep',
            'email' => 'test@test.com',
            'phone' => '123',
            'status' => 'Active'
        ]);
        $player1 = Player::create(['first_name' => 'P1', 'last_name' => 'L1', 'club_id' => $club->id]);
        $player2 = Player::create(['first_name' => 'P2', 'last_name' => 'L2', 'club_id' => $club->id]);

        $running = RunningTournament::create([
            'title' => 'NS Team Cup',
            'team_results' => [
                'teams' => [
                    ['id' => 't1', 'name' => 'Team A', 'captain_id' => $player1->id, 'player_ids' => [$player1->id, $player2->id]],
                ]
            ]
        ]);

        $set = BoardSet::create(['running_tournament_id' => $running->id, 'name' => 'Kolo 1']);
        Board::create([
            'board_set_id' => $set->id,
            'board_number' => 1,
            'vulnerability' => 'None',
            'cards_north' => [], 'cards_south' => [], 'cards_east' => [], 'cards_west' => []
        ]);

        $published = $running->publishToTournament();

        $this->assertDatabaseHas('tournaments', ['id' => $published->id, 'title' => 'NS Team Cup']);
        
        $freshRunning = RunningTournament::find($running->id);
        $this->assertEquals($published->id, $freshRunning->tournament_id);
        
        $this->assertCount(1, $published->boardSets);
        $this->assertCount(1, $published->boardSets->first()->boards);
        $this->assertCount(2, $published->players);
    }
}
