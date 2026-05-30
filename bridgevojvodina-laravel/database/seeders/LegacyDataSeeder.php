<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LegacyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Migrate Clubs
        $klubovi = DB::table('klub')->get();
        foreach ($klubovi as $klub) {
            DB::table('clubs')->insert([
                'id' => $klub->Id,
                'name' => $klub->Naziv,
                'city' => $klub->Mesto,
                'address' => $klub->Adresa,
                'representative' => $klub->Zastupnik,
                'email' => $klub->Email,
                'phone' => $klub->Telefon,
                'status' => $klub->Status == 'Aktivan' ? 'Active' : 'Inactive',
                'link' => $klub->Link,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Migrate Players
        $igraci = DB::table('igrac')->get();
        foreach ($igraci as $igrac) {
            DB::table('players')->insert([
                'id' => $igrac->Id,
                'first_name' => $igrac->Ime,
                'last_name' => $igrac->Prezime,
                'club_id' => $igrac->KlubId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Migrate Events
        $dogadjaji = DB::table('dogadjaj')->get();
        foreach ($dogadjaji as $dogadjaj) {
            DB::table('events')->insert([
                'id' => $dogadjaj->Id,
                'title' => $dogadjaj->Naziv,
                'description' => $dogadjaj->Opis,
                'date' => $dogadjaj->Datum,
                'club_id' => $dogadjaj->KorisnikovKlubId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
