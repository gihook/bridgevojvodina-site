<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@bridgevojvodina.rs'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => \App\Models\User::ROLE_ADMIN,
            ]
        );
    }
}
