<?php

namespace App\Http\Controllers;

use App\Models\Club;
use Illuminate\Http\Request;

class ClubControoller extends Controller
{
    public function show(Request $request)
    {
        return response()->json(
            Club::all()
        );
    }

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
        ], 200);
    }

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
