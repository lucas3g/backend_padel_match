<?php

namespace App\Http\Controllers;

use App\Models\Club;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Clubes Favoritos",
 *     description="Gerenciamento dos clubes favoritos do jogador"
 * )
 */
class PlayerFavoriteClubController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/me/player/clubes-favoritos",
     *     tags={"Clubes Favoritos"},
     *     summary="Lista os clubes favoritos do player",
     *     description="Retorna os clubes marcados como favoritos pelo jogador autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de clubes favoritos",
     *         @OA\JsonContent(type="array", @OA\Items(type="object"))
     *     ),
     *     @OA\Response(response=404, description="Usuário não possui player vinculado")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 404);
        }

        return response()->json($player->favoriteClubs);
    }

    /**
     * @OA\Post(
     *     path="/api/me/player/clubes-favoritos/{club}",
     *     tags={"Clubes Favoritos"},
     *     summary="Adiciona um clube aos favoritos",
     *     description="Marca um clube como favorito para o jogador autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="club",
     *         in="path",
     *         required=true,
     *         description="ID do clube",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=201, description="Clube adicionado aos favoritos"),
     *     @OA\Response(response=404, description="Clube ou player não encontrado"),
     *     @OA\Response(response=409, description="Clube já está nos favoritos")
     * )
     */
    public function store(Request $request, Club $club): JsonResponse
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 404);
        }

        if ($player->favoriteClubs()->where('club_id', $club->id)->exists()) {
            return response()->json(['message' => 'Clube já está nos favoritos'], 409);
        }

        $player->favoriteClubs()->attach($club->id);

        return response()->json([
            'message' => 'Clube adicionado aos favoritos',
            'clube'   => $player->favoriteClubs()->find($club->id),
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/me/player/clubes-favoritos/{club}",
     *     tags={"Clubes Favoritos"},
     *     summary="Remove um clube dos favoritos",
     *     description="Desmarca um clube como favorito do jogador autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="club",
     *         in="path",
     *         required=true,
     *         description="ID do clube",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *
     *     @OA\Response(response=200, description="Clube removido dos favoritos"),
     *     @OA\Response(response=404, description="Clube não está nos favoritos ou player não encontrado")
     * )
     */
    public function destroy(Request $request, Club $club): JsonResponse
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 404);
        }

        if (!$player->favoriteClubs()->where('club_id', $club->id)->exists()) {
            return response()->json(['message' => 'Clube não está nos favoritos'], 404);
        }

        $player->favoriteClubs()->detach($club->id);

        return response()->json(['message' => 'Clube removido dos favoritos']);
    }

    /**
     * @OA\Put(
     *     path="/api/me/player/clubes-favoritos",
     *     tags={"Clubes Favoritos"},
     *     summary="Sincroniza a lista de clubes favoritos",
     *     description="Substitui toda a lista de favoritos pelos IDs informados. Envie array vazio para limpar todos.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"clube_ids"},
     *             @OA\Property(
     *                 property="clube_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={1, 3, 7}
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Lista de favoritos sincronizada"),
     *     @OA\Response(response=404, description="Usuário não possui player vinculado"),
     *     @OA\Response(response=422, description="IDs inválidos")
     * )
     */
    public function sync(Request $request): JsonResponse
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json(['message' => 'Usuário não possui player vinculado'], 404);
        }

        $data = $request->validate([
            'clube_ids'   => 'required|array',
            'clube_ids.*' => 'integer|exists:clubs,id',
        ], [
            'clube_ids.required'  => 'A lista de clubes é obrigatória.',
            'clube_ids.array'     => 'clube_ids deve ser um array.',
            'clube_ids.*.exists'  => 'Um ou mais clubes informados não existem.',
        ]);

        $player->favoriteClubs()->sync($data['clube_ids']);

        return response()->json([
            'message' => 'Clubes favoritos atualizados com sucesso',
            'clubes'  => $player->favoriteClubs,
        ]);
    }
}
