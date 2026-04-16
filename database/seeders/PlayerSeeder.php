<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;

class PlayerSeeder extends Seeder
{
    public function run(): void
    {
        if (Player::count() >= 20) {
            $this->command->info('PlayerSeeder: jogadores já existem, pulando.');
            return;
        }

        for ($level = 1; $level <= 10; $level++) {
            Player::factory()
                ->count(2)
                ->withLevel($level)
                ->create();
        }

        $this->command->info('PlayerSeeder: 20 jogadores criados (2 por nível 1–10).');
    }
}
