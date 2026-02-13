<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameInvitation;
use App\Models\Player;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Game Invitations",
 *     description="Gerenciamento de convites para partidas privadas"
 * )
 */
class GameInvitationController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/game/{game}/invite/{player}",
     *     tags={"Game Invitations"},
     *     summary="Convidar jogador para partida privada",
     *     description="O proprietário da partida convida um jogador para participar de uma partida privada",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="game",
     *         in="path",
     *         required=true,
     *         description="ID da partida",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="player",
     *         in="path",
     *         required=true,
     *         description="ID do jogador a convidar",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Convite enviado com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Erro de validação"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sem permissão para convidar"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Jogador não encontrado"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Convite já existe ou jogador já está na partida"
     *     )
     * )
     */
    public function invite(Request $request, Game $game, $playerId)
    {
        $user = $request->user();

        if ($user->cannot('invite', $game)) {
            return response()->json([
                'message' => 'Sem permissão para convidar jogadores para esta partida'
            ], 403);
        }

        $player = $request->user()->player;

        if ($player->id == $playerId) {
            return response()->json([
                'message' => 'Não é possível convidar a si mesmo'
            ], 400);
        }

        $invitedPlayer = Player::find($playerId);
        if (!$invitedPlayer) {
            return response()->json([
                'message' => 'Jogador não encontrado'
            ], 404);
        }

        if ($game->players()->where('players.id', $playerId)->exists()) {
            return response()->json([
                'message' => 'Jogador já está na partida'
            ], 409);
        }

        if ($game->max_players && $game->players()->count() >= $game->max_players) {
            return response()->json([
                'message' => 'A partida já atingiu o número máximo de jogadores'
            ], 409);
        }

        $existingInvite = GameInvitation::where('game_id', $game->id)
            ->where('player_id', $playerId)
            ->first();

        if ($existingInvite) {
            if ($existingInvite->status === 'rejected') {
                $existingInvite->update(['status' => 'pending']);
                return response()->json([
                    'message' => 'Convite reenviado',
                    'data' => $existingInvite
                ], 201);
            }

            return response()->json([
                'message' => 'Já existe um convite para este jogador'
            ], 409);
        }

        $invitation = GameInvitation::create([
            'game_id' => $game->id,
            'player_id' => $playerId,
            'invited_by' => $player->id,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Convite enviado',
            'data' => $invitation
        ], 201);
    }

    /**
     * @OA\Delete(
     *     path="/api/game/{game}/invite/{player}",
     *     tags={"Game Invitations"},
     *     summary="Cancelar convite",
     *     description="O proprietário cancela um convite enviado para um jogador",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="game",
     *         in="path",
     *         required=true,
     *         description="ID da partida",
     *         @OA\Schema(type="integer")
     *     ),
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
     *         description="Convite cancelado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Convite cancelado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sem permissão"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Convite não encontrado"
     *     )
     * )
     */
    public function cancelInvite(Request $request, Game $game, $playerId)
    {
        $user = $request->user();

        if ($user->cannot('invite', $game)) {
            return response()->json([
                'message' => 'Sem permissão para gerenciar convites desta partida'
            ], 403);
        }

        $deleted = GameInvitation::where('game_id', $game->id)
            ->where('player_id', $playerId)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'message' => 'Convite não encontrado'
            ], 404);
        }

        return response()->json([
            'message' => 'Convite cancelado'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/game/invitations",
     *     tags={"Game Invitations"},
     *     summary="Lista convites recebidos",
     *     description="Retorna os convites pendentes de partidas privadas para o jogador autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de convites",
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
    public function myInvitations(Request $request)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $invitations = GameInvitation::where('player_id', $player->id)
            ->where('status', 'pending')
            ->with([
                'game:id,title,description,type,data_time,club_id,status,max_players,game_type',
                'game.club:id,name,city,state',
                'game.owner:id,full_name',
                'invitedBy:id,full_name'
            ])
            ->get()
            ->each(fn ($inv) => $inv->makeHidden(['created_at', 'updated_at']));

        return response()->json($invitations);
    }

    /**
     * @OA\Post(
     *     path="/api/game/invitation/{invitation}/accept",
     *     tags={"Game Invitations"},
     *     summary="Aceitar convite",
     *     description="Aceita um convite para partida privada. Após aceitar, o jogador pode entrar na partida via endpoint de join.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="invitation",
     *         in="path",
     *         required=true,
     *         description="ID do convite",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Convite aceito",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Convite aceito")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sem permissão para aceitar este convite"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Convite não encontrado"
     *     )
     * )
     */
    public function accept(Request $request, $invitationId)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $invitation = GameInvitation::where('id', $invitationId)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Convite não encontrado'
            ], 404);
        }

        if ($invitation->player_id !== $player->id) {
            return response()->json([
                'message' => 'Sem permissão para aceitar este convite'
            ], 403);
        }

        $invitation->update(['status' => 'accepted']);

        return response()->json([
            'message' => 'Convite aceito'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/game/invitation/{invitation}/reject",
     *     tags={"Game Invitations"},
     *     summary="Rejeitar convite",
     *     description="Rejeita um convite para partida privada",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="invitation",
     *         in="path",
     *         required=true,
     *         description="ID do convite",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Convite rejeitado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Convite rejeitado")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Sem permissão para rejeitar este convite"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Convite não encontrado"
     *     )
     * )
     */
    public function reject(Request $request, $invitationId)
    {
        $player = $request->user()->player;

        if (!$player) {
            return response()->json([
                'message' => 'Usuário não possui player vinculado'
            ], 400);
        }

        $invitation = GameInvitation::where('id', $invitationId)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return response()->json([
                'message' => 'Convite não encontrado'
            ], 404);
        }

        if ($invitation->player_id !== $player->id) {
            return response()->json([
                'message' => 'Sem permissão para rejeitar este convite'
            ], 403);
        }

        $invitation->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Convite rejeitado'
        ]);
    }
}
