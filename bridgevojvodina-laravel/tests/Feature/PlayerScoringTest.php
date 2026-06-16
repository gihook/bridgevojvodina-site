<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Player;
use App\Models\Tournament;
use App\Models\TournamentConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlayerScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_connect_account_to_available_player(): void
    {
        $user = User::factory()->create();
        $player = $this->createPlayer('Ana', 'Anic');

        $response = $this->actingAs($user)->post(route('scoring.player.link'), [
            'player_id' => $player->id,
        ]);

        $response->assertRedirect(route('scoring.index'));
        $this->assertSame($player->id, $user->fresh()->player_id);
    }

    public function test_user_cannot_connect_to_player_already_claimed_by_another_account(): void
    {
        $player = $this->createPlayer('Ana', 'Anic');
        User::factory()->create(['player_id' => $player->id]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('scoring.player.link'), [
            'player_id' => $player->id,
        ]);

        $response->assertSessionHasErrors('player_id');
        $this->assertNull($user->fresh()->player_id);
    }

    public function test_admin_can_start_match_and_assigned_player_can_enter_scores_until_finished(): void
    {
        [$tournament, $admin, $player] = $this->createScoringTournament(matchStatus: 'pending', roundStatus: 'idle');
        $user = User::factory()->create(['player_id' => $player->id]);

        $this->actingAs($user)
            ->get(route('scoring.index'))
            ->assertStatus(200)
            ->assertDontSee('Enter Scores');

        $this->actingAs($admin)
            ->patch(route('tournaments.rounds.matches.status.update', [$tournament, 'r1', 'm1']), [
                'status' => 'inProgress',
            ])
            ->assertRedirect();

        $tournament->refresh();
        $this->assertSame('inProgress', $tournament->team_results->rounds[0]->status);
        $this->assertSame('inProgress', $tournament->team_results->rounds[0]->matches[0]->status);

        $this->actingAs($user)
            ->get(route('scoring.index'))
            ->assertStatus(200)
            ->assertSee('BridgeMate Cup')
            ->assertSee('Enter Scores');

        $this->actingAs($user)
            ->get(route('scoring.room.show', [$tournament, 'r1', 'm1', 'open']))
            ->assertStatus(200);

        $this->actingAs($admin)
            ->patch(route('tournaments.rounds.matches.status.update', [$tournament, 'r1', 'm1']), [
                'status' => 'complete',
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->get(route('scoring.room.show', [$tournament, 'r1', 'm1', 'open']))
            ->assertForbidden();
    }

    public function test_player_score_update_does_not_return_match_imps_during_active_match(): void
    {
        [$tournament, $admin, $player] = $this->createScoringTournament(matchStatus: 'inProgress');
        $user = User::factory()->create(['player_id' => $player->id]);

        $response = $this->actingAs($user)->patchJson(route('scoring.board.update', [$tournament, 'r1', 'm1', 'open', 1]), [
            'contract_level' => 4,
            'contract_suit' => 'S',
            'contract_risk' => 1,
            'declarer' => 'N',
            'tricks' => 10,
            'lead' => 'HK',
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $payload = $response->json();

        $this->assertArrayNotHasKey('match_home_imp', $payload);
        $this->assertArrayNotHasKey('match_away_imp', $payload);
        $this->assertArrayNotHasKey('home_imp', $payload['board']);
        $this->assertArrayNotHasKey('away_imp', $payload['board']);

        $tournament->refresh();
        $savedBoard = $tournament->team_results->rounds[0]->matches[0]->boards[0];
        $this->assertSame(420, $savedBoard->home_score);
        $this->assertSame('4S', $savedBoard->home_contract);
        $this->assertSame('N', $savedBoard->home_declarer);
        $this->assertSame(10, $savedBoard->home_tricks);
        $this->assertSame('HK', $savedBoard->home_lead);
        $this->assertSame($user->id, $savedBoard->home_updated_by);

        $this->actingAs($admin)
            ->getJson(route('tournaments.match.room.state', [$tournament, 'r1', 'm1', 'open']))
            ->assertOk()
            ->assertJsonPath('boards.0.home_score', 420)
            ->assertJsonPath('boards.0.current_room_score', 420)
            ->assertJsonPath('boards.0.current_room_contract_base', '4S')
            ->assertJsonMissingPath('boards.0.home_imp')
            ->assertJsonMissingPath('match_home_imp');
    }

    public function test_admin_edit_uses_live_published_scores_when_stale_draft_has_same_id(): void
    {
        [$tournament, $admin, $player] = $this->createScoringTournament(matchStatus: 'inProgress');
        $draft = new TournamentConfiguration();
        $draft->id = $tournament->id;
        $draft->title = $tournament->title;
        $draft->description = $tournament->description;
        $draft->details = $tournament->details;
        $draft->user_id = $admin->id;
        $draft->team_results = $tournament->team_results;
        $draft->save();

        $user = User::factory()->create(['player_id' => $player->id]);

        $this->actingAs($user)->patchJson(route('scoring.board.update', [$tournament, 'r1', 'm1', 'open', 1]), [
            'contract_level' => 4,
            'contract_suit' => 'S',
            'contract_risk' => 1,
            'declarer' => 'N',
            'tricks' => 10,
        ])->assertOk();

        $this->actingAs($admin)
            ->get(route('tournaments.edit', $tournament))
            ->assertOk()
            ->assertSee('B1 4S +420');
    }

    public function test_player_scoring_room_only_receives_current_room_board_data(): void
    {
        [$tournament, , $player] = $this->createScoringTournament(matchStatus: 'inProgress');
        $user = User::factory()->create(['player_id' => $player->id]);

        $results = $tournament->team_results;
        $board = $results->rounds[0]->matches[0]->boards[0];
        $board->home_contract = '4S';
        $board->home_declarer = 'N';
        $board->home_tricks = 10;
        $board->home_score = 420;
        $board->away_contract = '3NT';
        $board->away_declarer = 'E';
        $board->away_tricks = 9;
        $board->away_score = -400;
        $tournament->team_results = $results;
        $tournament->save();

        $this->actingAs($user)
            ->get(route('scoring.room.show', [$tournament, 'r1', 'm1', 'open']))
            ->assertOk()
            ->assertSee('current_room_contract_base')
            ->assertSee('4S')
            ->assertDontSee('3NT')
            ->assertDontSee('away_contract')
            ->assertDontSee('away_score')
            ->assertDontSee('home_contract')
            ->assertDontSee('home_score');

        $response = $this->actingAs($user)->patchJson(route('scoring.board.update', [$tournament, 'r1', 'm1', 'open', 1]), [
            'contract_level' => 4,
            'contract_suit' => 'S',
            'contract_risk' => 1,
            'declarer' => 'N',
            'tricks' => 10,
        ]);

        $response
            ->assertOk()
            ->assertJsonMissingPath('board.away_contract')
            ->assertJsonMissingPath('board.away_score')
            ->assertJsonMissingPath('board.home_contract')
            ->assertJsonMissingPath('board.home_score')
            ->assertJsonPath('board.current_room_contract_base', '4S')
            ->assertJsonPath('board.current_room_score', 420);
    }

    public function test_player_can_sit_leave_and_resit_when_match_is_started(): void
    {
        [$tournament, , $player] = $this->createScoringTournament(matchStatus: 'inProgress', seatPlayers: false);
        $user = User::factory()->create(['player_id' => $player->id]);

        $this->actingAs($user)
            ->get(route('scoring.index'))
            ->assertStatus(200)
            ->assertSee('Sit')
            ->assertSee('Empty');

        $this->actingAs($user)
            ->get(route('scoring.room.show', [$tournament, 'r1', 'm1', 'open']))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('scoring.match.sit', [$tournament, 'r1', 'm1']), [
                'room' => 'open',
                'position' => 'N',
            ])
            ->assertRedirect();

        $tournament->refresh();
        $this->assertSame($player->id, $tournament->team_results->rounds[0]->matches[0]->open_ns_ids[0]);

        $this->actingAs($user)
            ->get(route('scoring.room.show', [$tournament, 'r1', 'm1', 'open']))
            ->assertStatus(200);

        $this->actingAs($user)
            ->delete(route('scoring.match.leave', [$tournament, 'r1', 'm1']))
            ->assertRedirect();

        $tournament->refresh();
        $this->assertNull($tournament->team_results->rounds[0]->matches[0]->open_ns_ids[0]);

        $this->actingAs($user)
            ->post(route('scoring.match.sit', [$tournament, 'r1', 'm1']), [
                'room' => 'closed',
                'position' => 'E',
            ])
            ->assertRedirect();

        $tournament->refresh();
        $match = $tournament->team_results->rounds[0]->matches[0];
        $this->assertNull($match->open_ns_ids[0]);
        $this->assertSame($player->id, $match->closed_ew_ids[0]);
    }

    public function test_player_cannot_sit_in_opponents_seat_or_taken_seat(): void
    {
        [$tournament, , $player, $players] = $this->createScoringTournament(matchStatus: 'inProgress', seatPlayers: false);
        $user = User::factory()->create(['player_id' => $player->id]);

        $this->actingAs($user)
            ->post(route('scoring.match.sit', [$tournament, 'r1', 'm1']), [
                'room' => 'open',
                'position' => 'E',
            ])
            ->assertSessionHasErrors('seat');

        $results = $tournament->fresh()->team_results;
        $results->rounds[0]->matches[0]->open_ns_ids = [$players[1]->id, null];
        $tournament->team_results = $results;
        $tournament->save();

        $this->actingAs($user)
            ->post(route('scoring.match.sit', [$tournament, 'r1', 'm1']), [
                'room' => 'open',
                'position' => 'N',
            ])
            ->assertSessionHasErrors('seat');
    }

    public function test_player_can_sit_from_public_tournament_match_list(): void
    {
        [$tournament, , $player] = $this->createScoringTournament(matchStatus: 'inProgress', seatPlayers: false);
        $user = User::factory()->create(['player_id' => $player->id]);

        $this->actingAs($user)
            ->get(route('tournaments.show', $tournament))
            ->assertStatus(200)
            ->assertSee('0 : 0')
            ->assertSee('In progress')
            ->assertSee('Open Room')
            ->assertSee('Closed Room')
            ->assertDontSee('Hidden');

        $this->actingAs($user)
            ->post(route('scoring.match.sit', [$tournament, 'r1', 'm1']), [
                'room' => 'open',
                'enter_after_sit' => 1,
            ])
            ->assertRedirect(route('scoring.room.show', [$tournament, 'r1', 'm1', 'open']));

        $tournament->refresh();
        $this->assertSame($player->id, $tournament->team_results->rounds[0]->matches[0]->open_ns_ids[0]);
    }

    public function test_admin_can_set_match_board_count_when_starting_match(): void
    {
        [$tournament, $admin, $player] = $this->createScoringTournament(matchStatus: 'pending', roundStatus: 'idle');
        $user = User::factory()->create(['player_id' => $player->id]);

        $this->actingAs($admin)
            ->patch(route('tournaments.rounds.matches.status.update', [$tournament, 'r1', 'm1']), [
                'status' => 'inProgress',
                'boards_count' => 12,
            ])
            ->assertRedirect();

        $tournament->refresh();
        $match = $tournament->team_results->rounds[0]->matches[0];
        $this->assertSame(12, $match->boards_count);
        $this->assertCount(12, $match->boards);

        $this->actingAs($user)
            ->patchJson(route('scoring.board.update', [$tournament, 'r1', 'm1', 'open', 13]), [
                'contract_level' => 4,
                'contract_suit' => 'S',
                'contract_risk' => 1,
                'declarer' => 'N',
                'tricks' => 10,
            ])
            ->assertNotFound();
    }

    public function test_admin_can_save_match_board_count_without_changing_match_status(): void
    {
        [$tournament, $admin] = $this->createScoringTournament(matchStatus: 'inProgress');

        $this->actingAs($admin)
            ->patch(route('tournaments.rounds.matches.boards-count.update', [$tournament, 'r1', 'm1']), [
                'boards_count' => 12,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $tournament->refresh();
        $match = $tournament->team_results->rounds[0]->matches[0];

        $this->assertSame('inProgress', $match->status);
        $this->assertSame(12, $match->boards_count);
        $this->assertCount(12, $match->boards);
    }

    private function createScoringTournament(string $matchStatus = 'pending', string $roundStatus = 'inProgress', bool $seatPlayers = true): array
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $players = [
            $this->createPlayer('North', 'One'),
            $this->createPlayer('South', 'Two'),
            $this->createPlayer('East', 'Three'),
            $this->createPlayer('West', 'Four'),
            $this->createPlayer('Closed', 'Five'),
            $this->createPlayer('Closed', 'Six'),
            $this->createPlayer('Closed', 'Seven'),
            $this->createPlayer('Closed', 'Eight'),
        ];

        $tournament = Tournament::create([
            'title' => 'BridgeMate Cup',
            'description' => 'Scoring test',
            'details' => 'Details',
            'user_id' => $admin->id,
            'team_results' => [
                'teams' => [
                    ['id' => 't1', 'name' => 'Team A', 'captain_id' => $players[0]->id, 'player_ids' => [$players[0]->id, $players[1]->id, $players[6]->id, $players[7]->id], 'total_vp' => 0],
                    ['id' => 't2', 'name' => 'Team B', 'captain_id' => $players[2]->id, 'player_ids' => [$players[2]->id, $players[3]->id, $players[4]->id, $players[5]->id], 'total_vp' => 0],
                ],
                'rounds' => [[
                    'id' => 'r1',
                    'name' => 'Round 1',
                    'status' => $roundStatus,
                    'boards_per_round' => 1,
                    'matches' => [[
                        'id' => 'm1',
                        'home_team_id' => 't1',
                        'away_team_id' => 't2',
                        'home_imp' => 0,
                        'away_imp' => 0,
                        'home_vp' => 0,
                        'away_vp' => 0,
                        'status' => $matchStatus,
                        'open_ns_ids' => $seatPlayers ? [$players[0]->id, $players[1]->id] : [],
                        'open_ew_ids' => $seatPlayers ? [$players[2]->id, $players[3]->id] : [],
                        'closed_ns_ids' => $seatPlayers ? [$players[4]->id, $players[5]->id] : [],
                        'closed_ew_ids' => $seatPlayers ? [$players[6]->id, $players[7]->id] : [],
                        'boards' => [['board_number' => 1]],
                    ]],
                ]],
                'boards_per_round' => 1,
            ],
        ]);

        return [$tournament, $admin, $players[0], $players];
    }

    private function createPlayer(string $firstName, string $lastName): Player
    {
        $club = Club::first() ?: Club::create([
            'name' => 'NSBK',
            'city' => 'Novi Sad',
            'address' => 'A1',
            'representative' => 'R1',
            'email' => 'club@example.test',
            'phone' => '1',
            'status' => 'Active',
        ]);

        return Player::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'club_id' => $club->id,
        ]);
    }
}
