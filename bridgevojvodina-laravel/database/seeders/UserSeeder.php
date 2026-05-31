<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Stevan Miškov',
                'email' => 'asmiskov@bridgevojvodina.rs',
                'password' => Hash::make('test'),
                'role' => User::ROLE_ADMIN,
                'player_id' => 2,
            ],
            [
                'name' => 'Jovana Maričić',
                'email' => 'jovana@bridgevojvodina.rs',
                'password' => Hash::make('test'),
                'role' => User::ROLE_ADMIN,
                'player_id' => 6,
            ],
            [
                'name' => 'Nikola Đukanović',
                'email' => 'nikola@bridgevojvodina.rs',
                'password' => Hash::make('test'),
                'role' => User::ROLE_ADMIN,
                'player_id' => 77,
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(['email' => $userData['email']], $userData);
        }
    }
}
