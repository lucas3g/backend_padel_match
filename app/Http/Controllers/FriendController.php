<?php

namespace App\Http\Controllers;

use App\Models\Friend;
use App\Models\Player;
use App\Models\PlayerFavorite;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Friends",
 *     description="Gerenciamento de amizades e favoritos"
 * )
 */
class FriendController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/friends",
     *     tags={"Friends"},
     *     summary="Lista amigos do jogador",
     *     description="Retorna a lista de amigos aceitos do jogador autenticado, com indicação de favoritos",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de amigos",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Usuário não possui player vinculado"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $sentAccepted = Friend::where('player_id', $player->id)
            ->where('status', 'accepted')
            ->with('friend:id,full_name,level,side,profile_image_url')
            ->get()
            ->pluck('friend');

        $receivedAccepted = Friend::where('friend_id', $player->id)
            ->where('status', 'accepted')
            ->with('player:id,full_name,level,side,profile_image_url')
            ->get()
            ->pluck('player');

        $friends = $sentAccepted->merge($receivedAccepted)->values();

        $favoriteIds = $player->favorites()->pluck('players.id')->toArray();

        $friends->each(function ($friend) use ($favoriteIds) {
            $friend->is_favorite = in_array($friend->id, $favoriteIds);
        });

        return response()->json($friends);
    }

    /**
     * @OA\Get(
     *     path="/api/friends/pending",
     *     tags={"Friends"},
     *     summary="Lista solicitações de amizade recebidas",
     *     description="Retorna as solicitações de amizade pendentes recebidas pelo jogador autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de solicitações pendentes",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Usuário não possui player vinculado"
     *     )
     * )
     */
    public function pending(Request $request)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $requests = Friend::where('friend_id', $player->id)
            ->where('status', 'pending')
            ->with('player:id,full_name,level,side,profile_image_url')
            ->get()
            ->each(fn ($f) => $f->makeHidden(['created_at', 'updated_at']));

        return response()->json($requests);
    }

    /**
     * @OA\Get(
     *     path="/api/friends/sent",
     *     tags={"Friends"},
     *     summary="Lista solicitações de amizade enviadas",
     *     description="Retorna as solicitações de amizade pendentes enviadas pelo jogador autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de solicitações enviadas",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Usuário não possui player vinculado"
     *     )
     * )
     */
    public function sent(Request $request)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $requests = Friend::where('player_id', $player->id)
            ->where('status', 'pending')
            ->with('friend:id,full_name,level,side,profile_image_url')
            ->get()
            ->each(fn ($f) => $f->makeHidden(['created_at', 'updated_at']));

        return response()->json($requests);
    }

    /**
     * @OA\Post(
     *     path="/api/friends/request/{player}",
     *     tags={"Friends"},
     *     summary="Enviar solicitação de amizade",
     *     description="Envia uma solicitação de amizade para outro jogador",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID do jogador",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Solicitação enviada com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Jogador não encontrado"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Solicitação já existe"
     *     )
     * )
     */
    public function sendRequest(Request $request, $playerId)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        if ($player->id == $playerId) {
            return response()->json([
                'message' => 'Não é possível enviar solicitação para si mesmo'
            ], 400);
        }

        $friendPlayer = Player::find($playerId);
        if (!$friendPlayer) {
            return response()->json([
                'message' => 'Jogador não encontrado'
            ], 404);
        }

        $existingFriendship = Friend::where(function ($q) use ($player, $playerId) {
            $q->where('player_id', $player->id)->where('friend_id', $playerId);
        })->orWhere(function ($q) use ($player, $playerId) {
            $q->where('player_id', $playerId)->where('friend_id', $player->id);
        })->first();

        if ($existingFriendship) {
            if ($existingFriendship->status === 'blocked') {
                return response()->json([
                    'message' => 'Não é possível enviar solicitação para este jogador'
                ], 400);
            }
            if ($existingFriendship->status === 'accepted') {
                return response()->json([
                    'message' => 'Vocês já são amigos'
                ], 409);
            }
            if ($existingFriendship->status === 'pending') {
                return response()->json([
                    'message' => 'Já existe uma solicitação pendente'
                ], 409);
            }
            if ($existingFriendship->status === 'rejected') {
                $existingFriendship->update([
                    'player_id' => $player->id,
                    'friend_id' => $playerId,
                    'status' => 'pending',
                ]);
                return response()->json([
                    'message' => 'Solicitação de amizade reenviada',
                    'data' => $existingFriendship
                ], 201);
            }
        }

        $friendship = Friend::create([
            'player_id' => $player->id,
            'friend_id' => $playerId,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Solicitação de amizade enviada',
            'data' => $friendship
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/friends/{friend}/accept",
     *     tags={"Friends"},
     *     summary="Aceitar solicitação de amizade",
     *     description="Aceita uma solicitação de amizade pendente. O parâmetro {friend} é o ID do registro na tabela friends.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="friend",
     *         in="path",
     *         required=true,
     *         description="ID da solicitação de amizade",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Solicitação aceita",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Solicitação de amizade aceita")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sem permissão para aceitar esta solicitação"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Solicitação não encontrada"
     *     )
     * )
     */
    public function accept(Request $request, $friendId)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $friendship = Friend::where('id', $friendId)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json([
                'message' => 'Solicitação de amizade não encontrada'
            ], 404);
        }

        if ($friendship->friend_id !== $player->id) {
            return response()->json([
                'message' => 'Sem permissão para aceitar esta solicitação'
            ], 403);
        }

        $friendship->update(['status' => 'accepted']);

        return response()->json([
            'message' => 'Solicitação de amizade aceita'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/friends/{friend}/reject",
     *     tags={"Friends"},
     *     summary="Rejeitar solicitação de amizade",
     *     description="Rejeita uma solicitação de amizade pendente. O parâmetro {friend} é o ID do registro na tabela friends.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="friend",
     *         in="path",
     *         required=true,
     *         description="ID da solicitação de amizade",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Solicitação rejeitada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Solicitação de amizade rejeitada")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sem permissão para rejeitar esta solicitação"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Solicitação não encontrada"
     *     )
     * )
     */
    public function reject(Request $request, $friendId)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $friendship = Friend::where('id', $friendId)
            ->where('status', 'pending')
            ->first();

        if (!$friendship) {
            return response()->json([
                'message' => 'Solicitação de amizade não encontrada'
            ], 404);
        }

        if ($friendship->friend_id !== $player->id) {
            return response()->json([
                'message' => 'Sem permissão para rejeitar esta solicitação'
            ], 403);
        }

        $friendship->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Solicitação de amizade rejeitada'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/friends/{player}",
     *     tags={"Friends"},
     *     summary="Remover amizade",
     *     description="Remove a amizade com outro jogador. Também remove dos favoritos se aplicável.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID do jogador",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Amizade removida",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Amizade removida")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Amizade não encontrada"
     *     )
     * )
     */
    public function remove(Request $request, $playerId)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $friendship = Friend::where(function ($q) use ($player, $playerId) {
            $q->where('player_id', $player->id)->where('friend_id', $playerId);
        })->orWhere(function ($q) use ($player, $playerId) {
            $q->where('player_id', $playerId)->where('friend_id', $player->id);
        })->where('status', 'accepted')->first();

        if (!$friendship) {
            return response()->json([
                'message' => 'Amizade não encontrada'
            ], 404);
        }

        PlayerFavorite::where('player_id', $player->id)
            ->where('favorite_player_id', $playerId)
            ->delete();

        PlayerFavorite::where('player_id', $playerId)
            ->where('favorite_player_id', $player->id)
            ->delete();

        $friendship->delete();

        return response()->json([
            'message' => 'Amizade removida'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/friends/{player}/block",
     *     tags={"Friends"},
     *     summary="Bloquear jogador",
     *     description="Bloqueia um jogador. Remove amizade e favoritos se existirem.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID do jogador a bloquear",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Jogador bloqueado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Jogador bloqueado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Jogador não encontrado"
     *     )
     * )
     */
    public function block(Request $request, $playerId)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        if ($player->id == $playerId) {
            return response()->json([
                'message' => 'Não é possível bloquear a si mesmo'
            ], 400);
        }

        $friendPlayer = Player::find($playerId);
        if (!$friendPlayer) {
            return response()->json([
                'message' => 'Jogador não encontrado'
            ], 404);
        }

        PlayerFavorite::where('player_id', $player->id)
            ->where('favorite_player_id', $playerId)
            ->delete();

        PlayerFavorite::where('player_id', $playerId)
            ->where('favorite_player_id', $player->id)
            ->delete();

        $friendship = Friend::where(function ($q) use ($player, $playerId) {
            $q->where('player_id', $player->id)->where('friend_id', $playerId);
        })->orWhere(function ($q) use ($player, $playerId) {
            $q->where('player_id', $playerId)->where('friend_id', $player->id);
        })->first();

        if ($friendship) {
            $friendship->update([
                'player_id' => $player->id,
                'friend_id' => $playerId,
                'status' => 'blocked',
            ]);
        } else {
            Friend::create([
                'player_id' => $player->id,
                'friend_id' => $playerId,
                'status' => 'blocked',
            ]);
        }

        return response()->json([
            'message' => 'Jogador bloqueado'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/friends/favorites",
     *     tags={"Friends"},
     *     summary="Lista amigos favoritos",
     *     description="Retorna a lista de amigos marcados como favoritos (amigos próximos) do jogador autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de favoritos",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Usuário não possui player vinculado"
     *     )
     * )
     */
    public function favorites(Request $request)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $favorites = $player->favorites()
            ->select('players.id', 'full_name', 'level', 'side', 'profile_image_url')
            ->get()
            ->each(fn ($p) => $p->pivot->makeHidden(['created_at', 'updated_at']));

        return response()->json($favorites);
    }

    /**
     * @OA\Post(
     *     path="/api/friends/{player}/favorite",
     *     tags={"Friends"},
     *     summary="Adicionar/remover amigo dos favoritos",
     *     description="Funciona como toggle: se o amigo já for favorito, remove. Se não for, adiciona. Somente amigos aceitos podem ser favoritados.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID do jogador amigo",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Status de favorito alterado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="is_favorite", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Jogadores não são amigos"
     *     )
     * )
     */
    public function toggleFavorite(Request $request, $playerId)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $friendship = Friend::where(function ($q) use ($player, $playerId) {
            $q->where('player_id', $player->id)->where('friend_id', $playerId);
        })->orWhere(function ($q) use ($player, $playerId) {
            $q->where('player_id', $playerId)->where('friend_id', $player->id);
        })->where('status', 'accepted')->first();

        if (!$friendship) {
            return response()->json([
                'message' => 'Somente amigos aceitos podem ser favoritados'
            ], 400);
        }

        $existing = PlayerFavorite::where('player_id', $player->id)
            ->where('favorite_player_id', $playerId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'message' => 'Removido dos favoritos',
                'is_favorite' => false
            ]);
        }

        PlayerFavorite::create([
            'player_id' => $player->id,
            'favorite_player_id' => $playerId,
        ]);

        return response()->json([
            'message' => 'Adicionado aos favoritos',
            'is_favorite' => true
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/friends/{player}/favorite",
     *     tags={"Friends"},
     *     summary="Remover amigo dos favoritos",
     *     description="Remove um amigo da lista de favoritos do jogador autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID do jogador amigo",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Removido dos favoritos",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Removido dos favoritos")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Favorito não encontrado"
     *     )
     * )
     */
    public function removeFavorite(Request $request, $playerId)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $deleted = PlayerFavorite::where('player_id', $player->id)
            ->where('favorite_player_id', $playerId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Favorito não encontrado'
            ], 404);
        }

        return response()->json([
            'message' => 'Removido dos favoritos'
        ]);
    }
}
