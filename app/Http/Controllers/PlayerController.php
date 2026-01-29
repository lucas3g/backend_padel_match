<?php

namespace App\Http\Controllers;

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
     *     path="/api/player",
     *     tags={"Player"},
     *     summary="Exibe o player do usuário autenticado",
     *     description="Retorna os dados do player associado ao usuário logado",
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
    public function show(Request $request)
    {
        return response()->json(
            $request->user()->player
        );
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
     *             @OA\Property(property="profile_image_url", type="string", example="https://site.com/foto.jpg")
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
     *         response=422,
     *         description="Usuário já possui um player cadastrado"
     *     )
     * )
     */
    public function store(Request $request)
    {
        if ($request->user()->player) {
            return response()->json([
                'message' => 'Usuário já possui um player cadastrado'
            ], 422);
        }

        $data = $request->validate([
            "full_name" => 'required|string|max:255',
            "phone" => 'nullable|string|max:20',
            "level" => 'required|integer',
            "side" => 'required|in:left,right,both',
            "bio" => 'nullable|string|max:2500',
            "profile_image_url" => 'nullable',
        ], [
            'full_name.required' => 'O nome do jogador é obrigatório.',
            'level.required' => 'A categoria do jogador é obrigatório.',
            'side.required' => 'O lado do jogador é obrigatório.',
        ]);

        $player = $request->user()->player()->create($data);

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
     *             @OA\Property(property="profile_image_url", type="string", example="https://site.com/foto.jpg")
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
        ], [
            'full_name.required' => 'O nome do jogador é obrigatório.',
            'level.required' => 'A categoria do jogador é obrigatório.',
            'side.required' => 'O lado do jogador é obrigatório.',
        ]);

        $player->update($data);

        return response()->json($player);
    }

    public function me(Request $request)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 422);
        } else {
            return response()->json([
                'message' => "Usuário vinculado ao player {$player->full_name}"
            ], 200);
        }
    }
}
