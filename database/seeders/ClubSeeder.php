<?php

namespace Database\Seeders;

use App\Models\Club;
use Illuminate\Database\Seeder;

class ClubSeeder extends Seeder
{
    public function run(): void
    {
        Club::updateOrCreate(
            ['name' => 'PadelMatch Club'],
            [
                'description'  => 'Clube padrão do sistema PadelMatch',
                'address'      => 'Rua das Quadras, 100',
                'city'         => 3550308, // São Paulo - código IBGE
                'state'        => 'SP',
                'neighborhood' => 'Centro',
                'zip_code'     => '01310-100',
                'open_time'    => '07:00',
                'close_time'   => '23:00',
                'active'       => true,
            ]
        );
    }
}
