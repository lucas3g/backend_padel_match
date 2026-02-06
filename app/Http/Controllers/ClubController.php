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
     *     description="Retorna os dados de todos os clubes cadastrados",
     *
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
    public function index(Request $request)
    {
        return response()->json(
            Club::all()
        );
    }

    /**
     * @OA\Get(
     *     path="/api/club/id",
     *     tags={"Clubs"},
     *     summary="Lista um clube",
     *     description="Retorna os dados do clube solicitado pelo id",
     *
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Dados do clube",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Clube não encontrado"
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $club = Club::find($id);

        if (!$club) {
            return response()->json([
                'message' => 'Clube não encontrado'
            ], 404);
        }

        return response()->json($club, 200);
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
     *         @OA\JsonContent(
     *             required={"name","description"},
     *             @OA\Property(property="name", type="string", maxLength=200, example="Clube Padel Center"),
     *             @OA\Property(property="description", type="string", maxLength=700, example="Clube de padel com quadras cobertas"),
     *             @OA\Property(property="document", type="string", example="12345678000199"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=150, example="contato@padelcenter.com"),
     *             @OA\Property(property="phone", type="string", maxLength=15, example="(49) 3333-3333"),
     *             @OA\Property(property="whatsapp", type="string", maxLength=15, example="(49) 99999-9999"),
     *             @OA\Property(property="address", type="string", maxLength=255, example="Rua das Quadras, 100"),
     *             @OA\Property(property="city", type="string", maxLength=100, example="Chapecó"),
     *             @OA\Property(property="state", type="string", maxLength=2, example="SC"),
     *             @OA\Property(property="zip_code", type="string", maxLength=20, example="89801-000"),
     *             @OA\Property(property="neighborhood", type="string", maxLength=50, example="Centro"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-27.1006),
     *             @OA\Property(property="longitude", type="number", format="float", example=-52.6153),
     *             @OA\Property(property="open_time", type="string", example="08:00"),
     *             @OA\Property(property="close_time", type="string", example="22:00"),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
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
     *         @OA\JsonContent(
     *             required={"name","description"},
     *             @OA\Property(property="name", type="string", maxLength=200, example="Clube Padel Center"),
     *             @OA\Property(property="description", type="string", maxLength=700, example="Clube de padel com quadras cobertas"),
     *             @OA\Property(property="document", type="string", example="12345678000199"),
     *             @OA\Property(property="email", type="string", format="email", maxLength=150, example="contato@padelcenter.com"),
     *             @OA\Property(property="phone", type="string", maxLength=15, example="(49) 3333-3333"),
     *             @OA\Property(property="whatsapp", type="string", maxLength=15, example="(49) 99999-9999"),
     *             @OA\Property(property="address", type="string", maxLength=255, example="Rua das Quadras, 100"),
     *             @OA\Property(property="city", type="string", maxLength=100, example="Chapecó"),
     *             @OA\Property(property="state", type="string", maxLength=2, example="SC"),
     *             @OA\Property(property="zip_code", type="string", maxLength=20, example="89801-000"),
     *             @OA\Property(property="neighborhood", type="string", maxLength=50, example="Centro"),
     *             @OA\Property(property="latitude", type="number", format="float", example=-27.1006),
     *             @OA\Property(property="longitude", type="number", format="float", example=-52.6153),
     *             @OA\Property(property="open_time", type="string", example="08:00"),
     *             @OA\Property(property="close_time", type="string", example="22:00"),
     *             @OA\Property(property="active", type="boolean", example=true)
     *         )
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
