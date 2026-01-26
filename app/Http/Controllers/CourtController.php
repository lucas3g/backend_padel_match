<?php

namespace App\Http\Controllers;

use App\Models\Club;
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
