<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class AuthController extends Controller
{
    /**
     * Registrar novo usuário.
     * 
     * @group Autenticação
     * 
     * @bodyParam name string required Nome do usuário.
     * @bodyParam email string required Email do usuário.
     * @bodyParam password string required Senha do usuário. Mínimo 8 caracteres.
     * @bodyParam password_confirmation string required Confirmação da senha. Deve ser igual à password.
     * @bodyParam role string Role do usuário. Aceita "user" ou "patron".
     * 
     * @response 201 {
     *   "message": "Usuário registrado com sucesso!",
     *   "token": "seu_token_aqui"
     * }
     * 
     * @response 401 {
     *   "message": "Usuário já está logado"
     * }
     */
    public function store(Request $request)
    {
        if (Auth::check()) {
            return response()->json([
                'message' => 'Usuário já está logado',
            ], 401);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->input('role')) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);
        }

        $token = $user->createToken('API Token')->plainTextToken;
        Auth::login($user);

        return response()->json([
            'message' => 'Usuário registrado com sucesso!',
            'token' => $token,
        ]);
    }

    /**
     * Login do usuário.
     * 
     * @group Autenticação
     * 
     * @bodyParam email string required Email do usuário.
     * @bodyParam password string required Senha do usuário.
     * 
     * @response 200 {
     *   "message": "Login realizado com sucesso!",
     *   "token": "seu_token_aqui"
     * }
     * 
     * @response 401 {
     *   "error": "Credenciais inválidas"
     * }
     */
    public function login(Request $request)
    {
        if (Auth::check()) {
            return response()->json([
                'message' => 'Usuário já está logado',
            ], 401);
        }

        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Credenciais inválidas',
            ], 401);
        }

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'message' => 'Login realizado com sucesso!',
            'token' => $token,
        ]);
    }

    /**
     * Logout do usuário autenticado.
     * 
     * @authenticated
     * @group Autenticação
     * @header Authorization Bearer {token} O token de autenticação JWT
     * 
     * @response 200 {
     *   "message": "Logout realizado com sucesso!"
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->tokens->each(function ($token) {
            $token->delete();
        });

        return response()->json([
            'message' => 'Logout realizado com sucesso!',
        ]);
    }
}
