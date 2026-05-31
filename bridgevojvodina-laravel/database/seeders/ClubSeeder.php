<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClubSeeder extends Seeder
{
    public function run(): void
    {
        $clubs = [
            [
                'id' => 1,
                'name' => 'Novosadski bridž klub NSBK',
                'city' => 'Novi Sad',
                'address' => 'Raše Radujkova 4',
                'representative' => 'Ivica Bošnjak',
                'email' => 'ivbdva@gmail.com',
                'phone' => '060 687 8746',
                'status' => 'Active',
                'link' => 'https://www.nsbk.rs/',
            ],
            [
                'id' => 2,
                'name' => 'Bridž klub NS-1',
                'city' => 'Novi Sad',
                'address' => 'Dragiše Brašovana 6',
                'representative' => 'Darko Parežanin',
                'email' => 'darkoparezanin@yahoo.com',
                'phone' => '063 508 011',
                'status' => 'Active',
                'link' => '',
            ],
            [
                'id' => 3,
                'name' => 'Bridž klub BNS',
                'city' => 'Banatsko Novo Selo',
                'address' => 'Maršala Tita 67',
                'representative' => 'Jonel Simu',
                'email' => 'jonel.simu@nis.rs',
                'phone' => '064 8888 981',
                'status' => 'Active',
                'link' => '',
            ],
            [
                'id' => 4,
                'name' => 'Bridž klub Panonija',
                'city' => 'Novi Sad',
                'address' => 'Blagoja Parovića 1',
                'representative' => 'Vladan Kardašević',
                'email' => 'bkpanonija@gmail.com',
                'phone' => '062 216 125',
                'status' => 'Active',
                'link' => 'http://bridzklub.com/',
            ],
            [
                'id' => 5,
                'name' => 'BK Kikinda',
                'city' => 'Kikinda',
                'address' => '.',
                'representative' => 'Stevan Božin',
                'email' => '.',
                'phone' => '.',
                'status' => 'Inactive',
                'link' => '',
            ],
            [
                'id' => 6,
                'name' => 'SBU Ekspert',
                'city' => 'Novi Sad',
                'address' => 'Danila Kiša 25',
                'representative' => 'Stojan Važić',
                'email' => '@',
                'phone' => '+381',
                'status' => 'Active',
                'link' => '',
            ],
        ];

        foreach ($clubs as $club) {
            DB::table('clubs')->updateOrInsert(['id' => $club['id']], array_merge($club, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
