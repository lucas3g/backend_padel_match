<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountDeletionController extends Controller
{
    public function show()
    {
        return view('account.delete');
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['password' => 'E-mail ou senha incorretos.']);
        }

        $user->tokens()->delete();
        $user->delete();

        return redirect()->route('account.deleted');
    }

    public function deleted()
    {
        return view('account.deleted');
    }

    // API: DELETE /api/account
    public function destroyApi(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Senha incorreta.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Conta excluída com sucesso.']);
    }
}
