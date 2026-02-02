<?php

namespace App\Http\Controllers;

use App\Models\Club;
use Illuminate\Http\Request;

class ClubCourtController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/club/id/courts",
     *     tags={"Club/Courts"},
     *     summary="Lista as quadras de um clube",
     *     description="lista os dados das quadras de um clube solicitado pelo id",     *
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lista as quadras de um clube",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(type="object")
     *         )
     *     )
     * )
     */
    public function index(Club $club)
    {
        $courts = $club->courts()->with('club')->get();

        return response()->json($courts);
    }
}
