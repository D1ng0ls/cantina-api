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
     * Remover perfil do usuário.
     * 
     * @authenticated
     * @group 2. Usuário
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "success": "Usuário removido com sucesso"
     * }
     */
    public function destroy(Request $request)
    {
        auth()->user()->delete();

        return response()->json([
            'success' => 'Usuário removido com sucesso',
        ]);
    }
}
