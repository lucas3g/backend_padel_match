<?php

namespace App\Http\Controllers;

use App\Models\Court;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(
            $request->user()->court
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'required|string',
            'address'       => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:100',
            'state'         => 'nullable|string|max:2',
            'postal_code'   => 'nullable|string|max:20',
            'latitude'      => 'nullable|numeric',
            'longitude'     => 'nullable|numeric',
            'court_type'    => 'nullable|string|max:50',
            'surface_type'  => 'nullable|string|max:50',
        ], [
            'name.required'        => 'O nome do clube é obrigatório.',
            'description.required' => 'A descrição do clube é obrigatória.',
        ]);

        $court = Court::create($data);

        return response()->json([
            'message' => 'Clube criado com sucesso',
            'data'    => $court
        ], 200);
    }

    public function update(Request $request)
    {
        $court = $request->user()->court;

        if (!$court) {
            return response()->json([
                'message' => 'Quadra não encontrado'
            ], 404);
        }

        $data = $request->validate(
            [
                'full_name' => 'required|string|max:25  5',
                'level' => 'required|integer',
                'side' => 'nullable|string|max:100',
            ],
            [
                'full_name.required' => 'O nome do jogador é obrigatório.',
                'level.required' => 'A categoria do jogador é obrigatório.',
                'side.required' => 'O lado do jogador é obrigatório.',
            ]
        );

        $court->update($data);

        return response()->json($court);
    }
}
