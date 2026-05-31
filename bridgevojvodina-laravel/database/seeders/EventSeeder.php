<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'id' => 2,
                'title' => 'Prvenstvo Evrope malih federacija u bridžu',
                'description' => 'Odigrava se od 20. do 23. oktobra 2019 u Novom Sadu na Ribarskom ostrvu.',
                'date' => '2019',
                'club_id' => 1,
            ],
            [
                'id' => 3,
                'title' => 'OPP',
                'description' => 'Otvoreno parsko prvenstvo Novog Sada za 2019.',
                'date' => '2019',
                'club_id' => 1,
            ],
            [
                'id' => 4,
                'title' => 'Meč za treće mesto Lige srbije za sezonu 2019/2020',
                'description' => "NS1 protiv NSBK\r\nNovi Sad, Hotel Aurora, 08.10.2020. i 15.10.2020 od 16.45h\r\n2 x 32 borda sa obračunom na 16 bordova.\r\nRezultat: NS1 +17    NSBK  -17",
                'date' => '2020',
                'club_id' => 1,
            ],
            [
                'id' => 5,
                'title' => 'Tužna vest',
                'description' => 'U subotu, 01.05.2021 napustio nas je naš Branislav Đuričić Đura (1960), istaknuti bridž igrač i reprezentativac. ',
                'date' => '2021',
                'club_id' => 1,
            ],
            [
                'id' => 6,
                'title' => 'Kvalifikacije za ligu Srbije za region Vojvodne',
                'description' => 'Kvalifikacioni turnir se igra 15.01.2022 u prostorijama Sportskog centra Medijana, Stojana Novakovića 2, sa početkom u 11.00 časova.\r\nIgra se 3 puta 16 bordova. Imp obračun.',
                'date' => '2022',
                'club_id' => 1,
            ],
            [
                'id' => 7,
                'title' => 'Novi Sad Bridge Festival - 2024',
                'description' => "May 8 - 12, 2024\r\nFor more details:\r\nhttps://bridgescanner.news/event/novi-sad-bridge-festival-2024",
                'date' => '2024',
                'club_id' => 1,
            ],
            [
                'id' => 8,
                'title' => 'Novi Sad Bridž festival 2025',
                'description' => 'Od 07.05. do 11.05.2025\r\nRezultati:   https://www.bridzs.hu/hu/versenyek-eredmenyek?vid=9131',
                'date' => '2025',
                'club_id' => 1,
            ],
        ];

        foreach ($events as $event) {
            DB::table('events')->updateOrInsert(['id' => $event['id']], array_merge($event, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
