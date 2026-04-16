<?php

namespace Database\Seeders;

use App\Models\Club;
use App\Models\Court;
use App\Models\Game;
use App\Models\GameSet;
use App\Models\Player;
use App\Services\RankingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RankingGameSeeder extends Seeder
{
    public function run(): void
    {
        if (Game::where('game_type', 'ranking')->count() > 0) {
            $this->command->info('RankingGameSeeder: partidas de ranking já existem, pulando.');
            return;
        }

        $players = Player::orderBy('id')->get();
        if ($players->count() < 20) {
            $this->command->error('RankingGameSeeder: execute PlayerSeeder primeiro (encontrados ' . $players->count() . ' jogadores).');
            return;
        }

        $rankingService = app(RankingService::class);

        $allClubs = $this->createAdditionalClubs();

        $courtsByClub = $allClubs->mapWithKeys(
            fn ($club) => [$club->id => $club->courts()->where('active', true)->pluck('id')->toArray()]
        );

        // Contadores de partidas por clube e por jogador para distribuição uniforme
        $gamesPerClub    = $allClubs->mapWithKeys(fn ($c) => [$c->id => 0])->toArray();
        $playerGameCount = $players->mapWithKeys(fn ($p) => [$p->id => 0])->toArray();

        $totalGames = 50;
        $startDate  = now()->subMonths(6);

        for ($gameIndex = 0; $gameIndex < $totalGames; $gameIndex++) {
            // 1. Clube com menos partidas
            asort($gamesPerClub);
            $clubId  = array_key_first($gamesPerClub);
            $club    = $allClubs->firstWhere('id', $clubId);
            $courts  = $courtsByClub[$clubId];
            $courtId = $courts[$gameIndex % count($courts)];

            // 2. 4 jogadores com menos partidas
            asort($playerGameCount);
            $pickedIds = array_slice(array_keys($playerGameCount), 0, 4);
            $picked    = $players->whereIn('id', $pickedIds)->values()->shuffle();

            $team1 = $picked->slice(0, 2)->values();
            $team2 = $picked->slice(2, 2)->values();

            // 3. Vencedor aleatório e sets
            $winner  = fake()->randomElement([1, 2]);
            $setData = $this->generateSets($winner);

            $t1Sets = collect($setData)->filter(fn ($s) => $s['team1_score'] > $s['team2_score'])->count();
            $t2Sets = collect($setData)->filter(fn ($s) => $s['team2_score'] > $s['team1_score'])->count();

            $dataTime = $startDate->copy()->addDays($gameIndex * 3)->setTime(
                fake()->randomElement([8, 10, 14, 16, 19]),
                fake()->randomElement([0, 30]),
                0
            );

            // 4. Criar partida (owner_player_id fora do fillable → forceCreate)
            $game = Game::forceCreate([
                'title'            => 'Ranking #' . ($gameIndex + 1),
                'type'             => 'private',
                'data_time'        => $dataTime,
                'club_id'          => $clubId,
                'court_id'         => $courtId,
                'owner_player_id'  => $team1->first()->id,
                'game_type'        => 'ranking',
                'status'           => 'completed',
                'winner_team'      => $winner,
                'team1_score'      => $t1Sets,
                'team2_score'      => $t2Sets,
                'duration_minutes' => fake()->randomElement([60, 75, 90]),
            ]);

            // 5. Inserir jogadores no pivot com time
            $now = now();
            foreach ($team1 as $player) {
                DB::table('game_players')->insert([
                    'game_id'    => $game->id,
                    'player_id'  => $player->id,
                    'team'       => 1,
                    'joined_at'  => $dataTime,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            foreach ($team2 as $player) {
                DB::table('game_players')->insert([
                    'game_id'    => $game->id,
                    'player_id'  => $player->id,
                    'team'       => 2,
                    'joined_at'  => $dataTime,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            // 6. Criar sets
            foreach ($setData as $set) {
                GameSet::create([
                    'game_id'     => $game->id,
                    'set_number'  => $set['set_number'],
                    'team1_score' => $set['team1_score'],
                    'team2_score' => $set['team2_score'],
                ]);
            }

            // 7. Processar ELO — fora de transação externa
            $rankingService->processRankingGame($game);

            // 8. Atualizar contadores
            $gamesPerClub[$clubId]++;
            foreach ($pickedIds as $pid) {
                $playerGameCount[$pid]++;
            }

            $this->command->getOutput()->write('.');
        }

        $this->command->newLine();
        $this->command->info("RankingGameSeeder: {$totalGames} partidas de ranking criadas e processadas.");
    }

    /**
     * Cria 2 clubes adicionais com 2 quadras cada.
     * Retorna todos os 3 clubes (incluindo o PadelMatch Club existente).
     */
    private function createAdditionalClubs(): \Illuminate\Support\Collection
    {
        $definitions = [
            [
                'club' => [
                    'name'         => 'PadelMatch Club',
                ],
            ],
            [
                'club' => [
                    'name'         => 'Arena Padel Jardins',
                    'description'  => 'Arena premium no coração dos Jardins',
                    'address'      => 'Av. Paulista, 2000',
                    'city'         => 3550308,
                    'state'        => 'SP',
                    'neighborhood' => 'Jardins',
                    'zip_code'     => '01310-200',
                    'open_time'    => '06:00',
                    'close_time'   => '22:00',
                    'active'       => true,
                ],
                'courts' => ['Quadra 1', 'Quadra 2'],
            ],
            [
                'club' => [
                    'name'         => 'Padel Club Moema',
                    'description'  => 'Clube exclusivo no bairro Moema',
                    'address'      => 'Rua Ministro Nelson Hungria, 500',
                    'city'         => 3550308,
                    'state'        => 'SP',
                    'neighborhood' => 'Moema',
                    'zip_code'     => '04638-000',
                    'open_time'    => '07:00',
                    'close_time'   => '23:00',
                    'active'       => true,
                ],
                'courts' => ['Quadra 1', 'Quadra 2'],
            ],
        ];

        $clubs = collect();

        foreach ($definitions as $def) {
            if (! isset($def['club']['address'])) {
                // Clube existente — apenas carrega
                $club = Club::where('name', $def['club']['name'])->firstOrFail();
            } else {
                $club = Club::updateOrCreate(['name' => $def['club']['name']], $def['club']);

                foreach ($def['courts'] as $i => $courtName) {
                    Court::updateOrCreate(
                        ['club_id' => $club->id, 'name' => $courtName],
                        [
                            'description'    => "Quadra " . ($i + 1) . " coberta de padel",
                            'type'           => 'padel',
                            'covered'        => true,
                            'price_per_hour' => 90.00 + ($i * 10),
                            'active'         => true,
                        ]
                    );
                }
            }

            $clubs->push($club->load('courts'));
        }

        return $clubs;
    }

    /**
     * Gera placar de sets para a partida.
     * 70% vitória direta (2–0), 30% com 3 sets (2–1).
     */
    private function generateSets(int $winnerTeam): array
    {
        $isStraight = fake()->boolean(70);
        $loserTeam  = $winnerTeam === 1 ? 2 : 1;
        $sets       = [];

        if ($isStraight) {
            for ($s = 1; $s <= 2; $s++) {
                $losingScore = fake()->randomElement([2, 3, 4]);
                $sets[]      = [
                    'set_number'  => $s,
                    'team1_score' => $winnerTeam === 1 ? 6 : $losingScore,
                    'team2_score' => $winnerTeam === 2 ? 6 : $losingScore,
                ];
            }
        } else {
            // Set 1: vencedor ganha
            $lose1  = fake()->randomElement([2, 3, 4]);
            $sets[] = [
                'set_number'  => 1,
                'team1_score' => $winnerTeam === 1 ? 6 : $lose1,
                'team2_score' => $winnerTeam === 2 ? 6 : $lose1,
            ];
            // Set 2: perdedor ganha
            $lose2  = fake()->randomElement([2, 3, 4]);
            $sets[] = [
                'set_number'  => 2,
                'team1_score' => $loserTeam === 1 ? 6 : $lose2,
                'team2_score' => $loserTeam === 2 ? 6 : $lose2,
            ];
            // Set 3: super tie-break (vencedor chega a 10)
            $superScore = fake()->randomElement([7, 8, 9]);
            $sets[]     = [
                'set_number'  => 3,
                'team1_score' => $winnerTeam === 1 ? 10 : $superScore,
                'team2_score' => $winnerTeam === 2 ? 10 : $superScore,
            ];
        }

        return $sets;
    }
}
