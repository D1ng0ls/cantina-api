<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends ApiController
{
    /**
     * Mostrar perfil do usuário.
     * 
     * @authenticated
     * @group 2. Usuário
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "name": "Nome do usuário",
     *   "email": "email@usuario.com",
     *   "student_id": "123456"
     * }
     */
    public function show(Request $request)
    {
        return response()->json([
            'name' => auth()->user()->name,
            'email' => auth()->user()->email,
            'student_id' => auth()->user()->student_id,
        ]);
    }

    /**
     * Atualizar perfil do usuário.
     * 
     * @authenticated
     * @group 2. Usuário
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @bodyParam name string required Nome do usuário.
     * @bodyParam email string required Email do usuário.
     * 
     * @response 200 {
     *   "success": "Usuário atualizado com sucesso"
     * }
     */
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore(auth()->id()),
            ],
        ]);

        auth()->user()->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return response()->json([
            'success' => 'Usuário atualizado com sucesso',
        ]);
    }

    /**
     * Alterar senha do usuário.
     * 
     * @authenticated
     * @group 2. Usuário
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @bodyParam old_password string required Senha antiga.
     * @bodyParam password string required Nova senha.
     * 
     * @response 200 {
     *   "success": "Senha alterada com sucesso"
     * }
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->old_password, auth()->user()->password)) {
            return response()->json([
                'error' => 'Senha antiga incorreta',
            ], 401);
        }

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => 'Senha alterada com sucesso',
        ]);
    }
}
