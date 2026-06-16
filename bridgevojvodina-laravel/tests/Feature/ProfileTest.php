<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_can_connect_account_to_available_player(): void
    {
        $user = User::factory()->create();
        $player = Player::create([
            'first_name' => 'Ana',
            'last_name' => 'Anic',
            'club_id' => null,
        ]);

        $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'player_id' => $player->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame($player->id, $user->fresh()->player_id);
    }

    public function test_profile_cannot_connect_to_player_claimed_by_another_user(): void
    {
        $player = Player::create([
            'first_name' => 'Ana',
            'last_name' => 'Anic',
            'club_id' => null,
        ]);
        User::factory()->create(['player_id' => $player->id]);
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->from('/profile')
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'player_id' => $player->id,
            ])
            ->assertSessionHasErrors('player_id')
            ->assertRedirect('/profile');

        $this->assertNull($user->fresh()->player_id);
    }

    public function test_admin_can_create_and_update_player_without_club(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $club = Club::create([
            'name' => 'NSBK',
            'city' => 'Novi Sad',
            'address' => 'A1',
            'representative' => 'R1',
            'email' => 'club@example.test',
            'phone' => '1',
            'status' => 'Active',
        ]);

        $this
            ->actingAs($admin)
            ->post(route('players.store'), [
                'first_name' => 'No',
                'last_name' => 'Club',
                'club_id' => null,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('players.index'));

        $player = Player::where('last_name', 'Club')->firstOrFail();
        $this->assertNull($player->club_id);

        $this
            ->actingAs($admin)
            ->put(route('players.update', $player), [
                'first_name' => 'Has',
                'last_name' => 'Club',
                'club_id' => $club->id,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('players.index'));

        $this->assertSame($club->id, $player->fresh()->club_id);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
