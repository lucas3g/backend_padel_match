<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Municípios",
 *     description="Consulta de municípios por UF"
 * )
 */
class MunicipioController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/municipios",
     *     tags={"Municípios"},
     *     summary="Lista municípios por UF",
     *     description="Retorna todos os municípios de uma UF, ordenados por nome",
     *
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="uf",
     *         in="query",
     *         required=true,
     *         description="Sigla da UF (ex: SC, SP, RS)",
     *         @OA\Schema(type="string", minLength=2, maxLength=2, example="SC")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista de municípios",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="codigo_ibge", type="integer", example=4204202),
     *                 @OA\Property(property="descricao", type="string", example="Chapecó"),
     *                 @OA\Property(property="uf", type="string", example="SC")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="UF não informada ou inválida"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autenticado"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $request->validate([
            'uf' => 'required|string|size:2',
        ]);

        $municipios = Municipio::where('uf', strtoupper($request->uf))
            ->orderBy('descricao')
            ->get(['codigo_ibge', 'descricao', 'uf']);

        return response()->json($municipios);
    }
}
