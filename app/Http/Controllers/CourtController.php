<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\Court;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Courts",
 *     description="Gerenciamento de quadras"
 * )
 */
class CourtController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/court/id",
     *     tags={"Courts"},
     *     summary="Lista uma quadra",
     *     description="Lista uma quadra conforme id solicitado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Dados da quadra",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Usuário não autenticado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Quadra não encontrada"
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        $court = Court::find($id);

        if (!$court) {
            return response()->json([
                'message' => 'Quadra não encontrada'
            ], 404);
        }

        return response()->json($court, 200);
    }

    /**
     * @OA\Post(
     *     path="/api/courts",
     *     tags={"Courts"},
     *     summary="Cria uma nova quadra",
     *     description="Cria uma quadra vinculada a um clube",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"club_id","name","description","type"},
     *             @OA\Property(property="club_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Quadra Central"),
     *             @OA\Property(property="description", type="string", example="Quadra coberta com iluminação"),
     *             @OA\Property(property="type", type="string", enum={"padel","beach_tenis"}, example="padel"),
     *             @OA\Property(property="covered", type="boolean", example=true),
     *             @OA\Property(property="price_per_hour", type="number", format="float", example=120),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="main_image_url", type="string", example="https://site.com/quadra.jpg")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Quadra criada com sucesso",
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
            'club_id'        => 'required|exists:clubs,id',
            'name'           => 'required|string|max:255',
            'description'    => 'required|string',
            'type'           => 'required|in:padel,beach_tenis',
            'covered'        => 'nullable|boolean',
            'price_per_hour' => 'nullable|numeric',
            'images'         => 'nullable',
            'main_image_url' => 'nullable|string'
        ], [
            'club_id.required'     => 'É obrigatório informar o clube',
            'name.required'        => 'O nome da quadra é obrigatório',
            'description.required' => 'A descrição da quadra é obrigatória',
        ]);

        $club  = Club::findOrFail($data['club_id']);
        $court = $club->courts()->create($data);

        return response()->json([
            'message' => 'Quadra criada com sucesso',
            'data'    => $court
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/courts/{id}",
     *     tags={"Courts"},
     *     summary="Atualiza uma quadra",
     *     description="Atualiza os dados de uma quadra existente",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID da quadra",
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"club_id","name","description","type"},
     *             @OA\Property(property="club_id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Quadra Principal"),
     *             @OA\Property(property="description", type="string", example="Quadra reformada"),
     *             @OA\Property(property="type", type="string", enum={"padel","beach_tenis"}, example="beach_tenis"),
     *             @OA\Property(property="covered", type="boolean", example=false),
     *             @OA\Property(property="price_per_hour", type="number", format="float", example=90),
     *             @OA\Property(property="images", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="main_image_url", type="string")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Quadra atualizada com sucesso",
     *         @OA\JsonContent(type="object")
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Quadra não encontrada"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $court = Court::find($id);

        if (!$court) {
            return response()->json([
                'message' => 'Quadra não encontrada'
            ], 404);
        }

        $data = $request->validate([
            'club_id'        => 'required',
            'name'           => 'required|string|max:255',
            'description'    => 'required|string',
            'type'           => 'required|in:padel,beach_tenis',
            'covered'        => 'nullable',
            'price_per_hour' => 'nullable',
            'images'         => 'nullable',
            'main_image_url' => 'nullable'

        ], [
            'club_id.required'     => 'O obrigatório informar o clube',
            'name.required'        =>  'O nome da quadra é obrigatório',
            'description.required' => 'A descrição do clube é obrigatória.',
        ]);

        $court->update($data);

        return response()->json($court);
    }
}
