<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        $players = [
            [2, 'Stevan', 'Miškov', 1], [3, 'Đuro', 'Opačić', 1], [4, 'Darko', 'Parežanin', 2],
            [5, 'Aleksandar', 'Fain', 4], [6, 'Jovana', 'Maričić', 6], [7, 'Jonel', 'Simu', 3],
            [8, 'Ljubomir', 'Blagojević', 3], [9, 'Nedeljko', 'Vuleta', 3], [10, 'Viorel', 'Beka', 3],
            [13, 'Dimitraki', 'Zipovski', 2], [14, 'Goran', 'Radišić', 2], [15, 'Gorana', 'Mitić', 2],
            [16, 'Ivo', 'Đukanović', 2], [18, 'Selena', 'Pepić', 6], [19, 'Stojan', 'Važić', 6],
            [20, 'Aleksandra', 'Ovuka', 1], [21, 'Atila', 'Baba', 1], [22, 'Bogdan', 'Veličković', 1],
            [23, 'Ivica', 'Bošnjak', 1], [24, 'Jovan', 'Poljački', 1], [26, 'Milina', 'Maksimović', 1],
            [27, 'Miloš', 'Vlaškalić', 1], [29, 'Obrad', 'Medić', 1], [30, 'Slobodan', 'Gužvica', 1],
            [31, 'Tamara', 'Nikolić', 1], [32, 'Tamara', 'Milutinović', 1], [33, 'Andrija', 'Gluščević', 4],
            [34, 'Branislav', 'Kardašević', 4], [36, 'Edita', 'Vrbaški', 4], [37, 'Jovan', 'Mojašević', 4],
            [38, 'Miladin', 'Dendić', 1], [39, 'Miloš', 'Kostevski', 4], [46, 'Vladan', 'Kardašević', 4],
            [48, 'Zoran', 'Ilijašević', 4], [49, 'Zoran', 'Veselinov', 4], [50, 'Vuk', 'Trnavac', 1],
            [51, 'Nebojša', 'Todorović', 2], [52, 'Marko', 'Mladenović', 2], [53, 'Marko', 'Gligorijević', 2],
            [54, 'Danko', 'Ukropina', 2], [55, 'Aleksa', 'Milićević', 2], [56, 'Stefan', 'Tambur', 2],
            [57, 'Zoran', 'Šarić', 1], [58, 'Dušica', 'Šarić', 1], [59, 'Anja', 'Ekres', 1],
            [60, 'Milica', 'Vojnović', 1], [61, 'Marko', 'Seizović', 6], [62, 'Filip', 'Jelić', 6],
            [63, 'Matko', 'Ferenca', 6], [64, 'Emanuel', 'Evačić', 6], [65, 'Ivan', 'Bilušić', 6],
            [66, 'Filip', 'Katušić', 6], [67, 'Srđan', 'Katušić', 6], [68, 'Igor', 'Stefanović', 6],
            [69, 'Milica', 'Jarić', 6], [70, 'Aleksandar', 'Stefanović', 6], [71, 'Aldo Giovani', 'Gerli', 6],
            [72, 'Piotr', 'Tuczynski', 6], [73, 'Pavle', 'Stanojević', 6], [74, 'Rastko', 'Stanojević', 6],
            [75, 'Antonia', 'Vladimir', 6], [76, 'Branka', 'Hadžić', 1], [77, 'Nikola', 'Đukanović', 1],
            [78, 'Lenka', 'Mihajlović', 1], [79, 'Atila', 'Baba', 1],
            [80, 'Boris', 'Jovanović', 6], [81, 'Lena', 'Gordić', 6], [82, 'Mihailo', 'Simić', 6],
            [83, 'Sofia', 'Martinović', 6], [84, 'Jovan', 'Dmitrović', 6],
            [85, 'Matej', 'Nežić', 6], [86, 'Leta', 'N.', 2]
        ];

        foreach ($players as $player) {
            DB::table('players')->updateOrInsert(['id' => $player[0]], [
                'first_name' => $player[1],
                'last_name' => $player[2],
                'club_id' => $player[3],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
