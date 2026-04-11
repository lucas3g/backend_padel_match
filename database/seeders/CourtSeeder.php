<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Court;
use Illuminate\Database\Seeder;

class CourtSeeder extends Seeder
{
    public function run(): void
    {
        $club = Club::where('name', 'PadelMatch Club')->firstOrFail();

        Court::updateOrCreate(
            ['club_id' => $club->id, 'name' => 'Quadra 1'],
            [
                'description'    => 'Quadra padrão coberta',
                'type'           => 'padel',
                'covered'        => true,
                'price_per_hour' => 100.00,
                'active'         => true,
            ]
        );
    }
}
