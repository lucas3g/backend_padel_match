<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Rules\ValidCodigoIbge;
use App\Rules\ValidUf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Player",
 *     description="Gerenciamento do perfil do jogador"
 * )
 */
class PlayerController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/players",
     *     tags={"Player"},
     *     summary="Lista todos os players",
     *     description="Retorna os dados de todos os players cadastrados, com filtros opcionais",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="full_name",
     *         in="query",
     *         required=false,
     *         description="Filtrar por nome do jogador (busca parcial)",
     *         @OA\Schema(type="string", example="João")
     *     ),
     *     @OA\Parameter(
     *         name="level",
     *         in="query",
     *         required=false,
     *         description="Filtrar por nível do jogador",
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="side",
     *         in="query",
     *         required=false,
     *         description="Filtrar por lado do jogador",
     *         @OA\Schema(type="string", enum={"left","right","both"}, example="right")
     *     ),
     *     @OA\Parameter(
     *         name="uf",
     *         in="query",
     *         required=false,
     *         description="Filtrar por UF (sigla do estado)",
     *         @OA\Schema(type="string", example="SP")
     *     ),
     *     @OA\Parameter(
     *         name="municipio_ibge",
     *         in="query",
     *         required=false,
     *         description="Filtrar por código IBGE do município (7 dígitos)",
     *         @OA\Schema(type="string", example="3550308")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de players",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $players = Player::query()
            ->when($request->query('full_name'), fn ($q, $name) => $q->where('full_name', 'like', "%{$name}%"))
            ->when($request->query('level'), fn ($q, $level) => $q->where('level', $level))
            ->when($request->query('side'), function ($q, $side) {
                if ($side === 'both') {
                    return $q;
                }
                return $q->whereIn('side', [$side, 'both']);
            })
            ->when($request->query('uf'), fn ($q, $uf) => $q->where('uf', strtoupper($uf)))
            ->when($request->query('municipio_ibge'), fn ($q, $codigo) => $q->where('municipio_ibge', $codigo))
            ->when($request->boolean('apenas_disponiveis'), fn ($q) => $q->disponiveis())
            ->with('municipio')
            ->get();

        return response()->json($players);
    }

    /**
     * @OA\Get(
     *     path="/api/player/id",
     *     tags={"Player"},
     *     summary="Exibe o player",
     *     description="Retorna os dados do player solicitado pelo id",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Player encontrado",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Usuário não autenticado"
     *     )
     * )
     */
    public function show($id)
    {
        $player = Player::find($id);

        if (!$player) {
            return response()->json([
                'message' => 'Jogador não encontrado'
            ], 404);
        }

        $player->load('municipio');

        return response()->json($player, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/player",
     *     tags={"Player"},
     *     summary="Cadastra um novo player",
     *     description="Cria o perfil de player para o usuário autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name","level","side"},
     *             @OA\Property(property="full_name", type="string", maxLength=255, example="João da Silva"),
     *             @OA\Property(property="phone", type="string", maxLength=20, example="(49) 99999-9999"),
     *             @OA\Property(property="level", type="integer", example=5),
     *             @OA\Property(property="side", type="string", enum={"left","right","both"}, example="right"),
     *             @OA\Property(property="bio", type="string", example="Jogador iniciante"),
     *             @OA\Property(property="profile_image_url", type="string", example="https://site.com/foto.jpg"),
     *             @OA\Property(property="uf", type="string", example="SP"),
     *             @OA\Property(property="municipio_ibge", type="string", example="3550308")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Player criado com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Jogador não encontrado"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Usuário já possui um player cadastrado"
     *     )
     * )
     */
    public function store(Request $request)
    {
        if ($request->user()->player) {
            return response()->json([
                'message' => 'Usuário já possui um player cadastrado'
            ], 409);
        }

        $data = $request->validate([
            "full_name" => 'required|string|max:255',
            "phone" => 'nullable|string|max:20',
            "level" => 'required|integer',
            "side" => 'required|in:left,right',
            "bio" => 'nullable|string|max:2500',
            "profile_image_url" => 'nullable',
            "uf" => ['required', 'string', 'size:2', new ValidUf()],
            "municipio_ibge" => ['required', 'integer', new ValidCodigoIbge($request->input('uf'))],
        ], [
            'full_name.required' => 'O nome do jogador é obrigatório.',
            'level.required' => 'A categoria do jogador é obrigatório.',
            'side.required' => 'O lado do jogador é obrigatório.',
            'uf.required' => 'A UF é obrigatória.',
            'uf.size' => 'A UF deve ter exatamente 2 caracteres.',
            'municipio_ibge.required' => 'O código do município é obrigatório.',
            'municipio_ibge.integer' => 'O código do município deve ser um número inteiro.',
        ]);

        if (isset($data['uf'])) {
            $data['uf'] = strtoupper($data['uf']);
        }
        $data['municipio_ibge'] = str_pad($data['municipio_ibge'], 7, '0', STR_PAD_LEFT);

        $player = $request->user()->player()->create($data);
        $player->load('municipio');

        return response()->json($player, 201);
    }

    /**
     * @OA\Put(
     *     path="/api/player",
     *     tags={"Player"},
     *     summary="Atualiza o player do usuário",
     *     description="Atualiza os dados do player associado ao usuário autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name","level","side"},
     *             @OA\Property(property="full_name", type="string", example="João da Silva"),
     *             @OA\Property(property="phone", type="string", example="(49) 99999-9999"),
     *             @OA\Property(property="level", type="integer", example=4),
     *             @OA\Property(property="side", type="string", enum={"left","right","both"}, example="both"),
     *             @OA\Property(property="bio", type="string", example="Jogador intermediário"),
     *             @OA\Property(property="profile_image_url", type="string", example="https://site.com/foto.jpg"),
     *             @OA\Property(property="uf", type="string", example="SP"),
     *             @OA\Property(property="municipio_ibge", type="string", example="3550308")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Player atualizado com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Player não encontrado"
     *     )
     * )
     */
    public function update(Request $request)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Player não encontrado'
            ], 404);
        }

        $data = $request->validate([
            "full_name" => 'required|string|max:255',
            "phone" => 'nullable|string|max:20',
            "level" => 'required|integer',
            "side" => 'required|in:left,right,both',
            "bio" => 'nullable|string|max:2500',
            "profile_image_url" => 'nullable',
            "uf" => ['required', 'string', 'size:2', new ValidUf()],
            "municipio_ibge" => ['required', 'integer', new ValidCodigoIbge($request->input('uf'))],
        ], [
            'full_name.required' => 'O nome do jogador é obrigatório.',
            'level.required' => 'A categoria do jogador é obrigatório.',
            'side.required' => 'O lado do jogador é obrigatório.',
            'uf.required' => 'A UF é obrigatória.',
            'uf.size' => 'A UF deve ter exatamente 2 caracteres.',
            'municipio_ibge.required' => 'O código do município é obrigatório.',
            'municipio_ibge.integer' => 'O código do município deve ser um número inteiro.',
        ]);

        if (isset($data['uf'])) {
            $data['uf'] = strtoupper($data['uf']);
        }
        $data['municipio_ibge'] = str_pad($data['municipio_ibge'], 7, '0', STR_PAD_LEFT);

        $player->update($data);
        $player->load('municipio');

        return response()->json($player);
    }

    /**
     * @OA\Get(
     *     path="/api/player/{player}/perfil",
     *     tags={"Player"},
     *     summary="Perfil completo do player",
     *     description="Retorna dados do player, estatísticas e últimos 5 resultados",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID do player",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Perfil do player",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=404, description="Jogador não encontrado")
     * )
     */
    public function perfil(Player $player)
    {
        $player->load('stats', 'municipio');

        $ultimosJogos = $player->games()
            ->where('games.status', 'completed')
            ->withPivot('team')
            ->orderByDesc('data_time')
            ->limit(5)
            ->get(['games.id', 'games.winner_team', 'games.data_time']);

        $ultimosResultados = $ultimosJogos->map(function ($game) {
            $meuTime = $game->pivot->team;
            if (!$meuTime || !$game->winner_team) {
                return 'sem_resultado';
            }
            return $meuTime === $game->winner_team ? 'vitoria' : 'derrota';
        })->values();

        return response()->json([
            'id'                 => $player->id,
            'full_name'          => $player->full_name,
            'level'              => $player->level,
            'side'               => $player->side,
            'bio'                => $player->bio,
            'profile_image_url'  => $player->profile_image_url,
            'data_nascimento'    => $player->data_nascimento,
            'posicao'            => $player->posicao,
            'uf'                  => $player->uf,
            'municipio_ibge'      => $player->municipio_ibge,
            'municipio_descricao' => $player->municipio?->descricao,
            'ranking_points'      => $player->ranking_points,
            'ranking_position'   => $player->ranking_position,
            'stats'                    => $player->stats,
            'ultimos_resultados'       => $ultimosResultados,
            'disponibilidade'          => $player->disponibilidade,
            'motivo_indisponibilidade' => $player->motivo_indisponibilidade,
            'disponivel_ate'           => $player->disponivel_ate?->toDateString(),
            'esta_disponivel'          => $player->esta_disponivel,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/player/{player}/partidas",
     *     tags={"Player"},
     *     summary="Histórico de partidas do player",
     *     description="Retorna o histórico paginado de partidas concluídas com sets, times e resultado. Use o parâmetro 'resultado' para filtrar.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID do player",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="resultado",
     *         in="query",
     *         required=false,
     *         description="Filtrar por resultado",
     *         @OA\Schema(type="string", enum={"todos","vitoria","derrota"}, example="vitoria")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista paginada de partidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=20)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Jogador não encontrado")
     * )
     */
    public function partidas(Request $request, Player $player)
    {
        $resultado = $request->query('resultado', 'todos');

        $query = $player->games()
            ->where('games.status', 'completed')
            ->withPivot(['team', 'joined_at'])
            ->with([
                'sets',
                'players' => fn ($q) => $q->withPivot('team')
                    ->select('players.id', 'full_name', 'level', 'side', 'profile_image_url'),
                'club:id,name',
                'court:id,name',
            ])
            ->orderByDesc('data_time');

        if ($resultado === 'vitoria') {
            $query->whereRaw('game_players.team = games.winner_team');
        } elseif ($resultado === 'derrota') {
            $query->whereRaw('game_players.team != games.winner_team AND games.winner_team IS NOT NULL');
        }

        $paginated = $query->paginate(15);

        $data = $paginated->getCollection()->map(function ($game) use ($player) {
            $meuTime    = $game->pivot->team;
            $winnerTeam = $game->winner_team;

            if (!$meuTime || !$winnerTeam) {
                $resultadoPartida = 'sem_resultado';
            } else {
                $resultadoPartida = $meuTime === $winnerTeam ? 'vitoria' : 'derrota';
            }

            $parceiros = $game->players
                ->filter(fn ($p) => $p->id !== $player->id && $p->pivot->team === $meuTime)
                ->map(fn ($p) => [
                    'id'                => $p->id,
                    'full_name'         => $p->full_name,
                    'level'             => $p->level,
                    'side'              => $p->side,
                    'profile_image_url' => $p->profile_image_url,
                ])
                ->values();

            $adversarios = $game->players
                ->filter(fn ($p) => $p->pivot->team !== $meuTime && $p->pivot->team !== null)
                ->map(fn ($p) => [
                    'id'                => $p->id,
                    'full_name'         => $p->full_name,
                    'level'             => $p->level,
                    'side'              => $p->side,
                    'profile_image_url' => $p->profile_image_url,
                ])
                ->values();

            return [
                'id'           => $game->id,
                'data'         => $game->data_time,
                'game_type'    => $game->game_type,
                'type'         => $game->type,
                'clube'        => $game->club?->name,
                'quadra'       => $game->court?->name,
                'resultado'    => $resultadoPartida,
                'meu_time'     => $meuTime,
                'time_vencedor' => $winnerTeam,
                'placar'       => [
                    'time1_sets' => $game->team1_score,
                    'time2_sets' => $game->team2_score,
                ],
                'sets'         => $game->sets->map(fn ($s) => [
                    'set'   => $s->set_number,
                    'time1' => $s->team1_score,
                    'time2' => $s->team2_score,
                ])->values(),
                'eu'         => [
                    'id'                => $player->id,
                    'full_name'         => $player->full_name,
                    'level'             => $player->level,
                    'side'              => $player->side,
                    'profile_image_url' => $player->profile_image_url,
                ],
                'parceiros'  => $parceiros,
                'adversarios' => $adversarios,
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/me/player",
     *     tags={"Player"},
     *     summary="Player vinculado ao usuário",
     *     description="Retorna se tem player vinculado ao usuário autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Dados do player",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Usuário não possui player vinculado"
     *     )
     * )
     */
    public function me(Request $request)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 404);
        }

        $player->load('municipio');

        return response()->json($player, 200);
    }

    /**
     * @OA\Patch(
     *     path="/api/me/player/disponibilidade",
     *     tags={"Player"},
     *     summary="Define disponibilidade do player",
     *     description="Permite ao player sinalizar indisponibilidade temporária (lesão, viagem, licença) com data opcional de retorno. A disponibilidade expira automaticamente na data informada.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"disponibilidade"},
     *             @OA\Property(property="disponibilidade", type="string", enum={"disponivel","machucado","viajando","licenca"}, example="machucado"),
     *             @OA\Property(property="motivo_indisponibilidade", type="string", maxLength=500, nullable=true, example="Lesão no tornozelo direito"),
     *             @OA\Property(property="disponivel_ate", type="string", format="date", nullable=true, example="2026-04-20")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Disponibilidade atualizada com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=404, description="Usuário não possui player vinculado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function definirDisponibilidade(Request $request): JsonResponse
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 404);
        }

        $data = $request->validate([
            'disponibilidade'          => 'required|in:disponivel,machucado,viajando,licenca',
            'motivo_indisponibilidade' => 'nullable|string|max:500',
            'disponivel_ate'           => 'nullable|date|after:today',
        ], [
            'disponibilidade.required' => 'O status de disponibilidade é obrigatório.',
            'disponibilidade.in'       => 'Status inválido. Use: disponivel, machucado, viajando ou licenca.',
            'disponivel_ate.after'     => 'A data de retorno deve ser posterior a hoje.',
        ]);

        if ($data['disponibilidade'] === 'disponivel') {
            $data['motivo_indisponibilidade'] = null;
            $data['disponivel_ate']           = null;
        }

        $player->update($data);

        return response()->json([
            'message'                  => 'Disponibilidade atualizada com sucesso',
            'disponibilidade'          => $player->disponibilidade,
            'motivo_indisponibilidade' => $player->motivo_indisponibilidade,
            'disponivel_ate'           => $player->disponivel_ate?->toDateString(),
            'esta_disponivel'          => $player->esta_disponivel,
        ]);
    }
}