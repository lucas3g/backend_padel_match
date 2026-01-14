<?php

namespace App\Http\Controllers;

use App\Models\Court;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(
            Court::all()
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

    public function update(Request $request, $id)
    {
        $court = Court::find($id);        

        if (!$court) {
            return response()->json([
                'message' => 'Quadra não encontrada'
            ], 404);
        }

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

        $court->update($data);

        return response()->json($court);
    }
}
