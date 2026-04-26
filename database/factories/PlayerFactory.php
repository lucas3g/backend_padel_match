<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'         => User::factory(),
            'full_name'       => fake('pt_BR')->name(),
            'phone'           => fake('pt_BR')->numerify('(##) 9####-####'),
            'level'           => fake()->numberBetween(1, 7),
            'side'            => fake()->randomElement(['left', 'right', 'both']),
            'sexo'            => fake()->randomElement(['masculino', 'feminino', 'prefiro_nao_informar']),
            'bio'             => fake('pt_BR')->sentence(10),
            'ranking_points'  => 1000,
            'ranking_position'=> null,
            'is_active'       => true,
            'is_verified'     => true,
            'disponibilidade' => 'disponivel',
            'uf'              => 'SP',
            'municipio_ibge'  => 3550308,
        ];
    }

    public function withLevel(int $level): static
    {
        return $this->state(['level' => $level]);
    }
}
