<?php

namespace App\Http\Controllers;

use App\Models\Club;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Clubs",
 *     description="Gerenciamento de clubes"
 * )
 */
class ClubController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/clubs",
     *     tags={"Clubs"},
     *     summary="Lista todos os clubes",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de clubes",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     )
     * )
     */
    public function show(Request $request)
    {
        return response()->json(
            Club::all()
        );
    }

    /**
     * @OA\Post(
     *     path="/api/clubs",
     *     tags={"Clubs"},
     *     summary="Cria um novo clube",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Clube criado com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:200',
            'description'   => 'required|string|max:700',
            'document'      => 'nullable',
            'email'         => 'nullable|string|max:150',
            'phone'         => 'nullable|string|max:15',
            'whatsapp'      => 'nullable|string|max:15',
            'address'       => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:2',
            'zip_code'      => 'nullable|string|max:20',
            'neighborhood'  => 'nullable|string|max:50',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'open_time'     => 'nullable',
            'close_time'    => 'nullable',
            'active'        => 'nullable'
        ], [
            'name.required'        => 'O nome do clube é obrigatório.',
            'description.required' => 'A descrição do clube é obrigatória.',
        ]);

        $club = Club::create($data);

        return response()->json([
            'message' => 'Clube criado com sucesso',
            'data'    => $club
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/clubs/{id}",
     *     tags={"Clubs"},
     *     summary="Atualiza um clube",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Clube atualizado",
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Clube não encontrado"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $club = Club::find($id);

        if (!$club) {
            return response()->json([
                'message' => 'Clube não encontrado'
            ], 404);
        }

        $data = $request->validate([
            'name'          => 'required|string|max:200',
            'description'   => 'required|string|max:700',
            'document'      => 'nullable',
            'email'         => 'nullable|string|max:150',
            'phone'         => 'nullable|string|max:15',
            'whatsapp'      => 'nullable|string|max:15',
            'address'       => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:2',
            'zip_code'      => 'nullable|string|max:20',
            'neighborhood'  => 'nullable|string|max:50',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'open_time'     => 'nullable',
            'close_time'    => 'nullable',
            'active'        => 'nullable'
        ], [
            'name.required'        => 'O nome do clube é obrigatório.',
            'description.required' => 'A descrição do clube é obrigatória.',
        ]);

        $club->update($data);

        return response()->json($club);
    }
}
