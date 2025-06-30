<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendPasswordResetCode;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Registrar novo usuário.
     * 
     * @group 1. Autenticação
     * 
     * @bodyParam name string required Nome do usuário.
     * @bodyParam email string required Email do usuário.
     * @bodyParam student_id string required ID do aluno.
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
    public function register(Request $request)
    {
        if (Auth::check()) {
            return response()->json([
                'message' => 'Usuário já está logado',
            ], 401);
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'student_id' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'student_id' => $request->student_id,
            'password' => Hash::make($request->password),
        ]);

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
     * @group 1. Autenticação
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
     * @group 1. Autenticação
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

    /**
     * Envia um código de redefinição de senha para o e-mail do usuário.
     *
     * @group 1. Autenticação
     *
     * @bodyParam email string required Email do usuário para enviar o código.
     *
     * @response 200 {
     * "message": "Se existir uma conta com este e-mail, um código de redefinição foi enviado."
     * }
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        // Resposta genérica por segurança, não informa se o e-mail existe ou não
        if (!$user) {
            return response()->json(['message' => 'Se existir uma conta com este e-mail, um código de redefinição foi enviado.']);
        }

        // Gera um código de 6 dígitos
        $code = rand(100000, 999999);

        // Salva o código no banco de dados (tabela password_reset_tokens)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($code), // Salva o HASH do código, nunca o código puro!
                'created_at' => Carbon::now()
            ]
        );

        // Envia o e-mail com o código (usando uma classe Mailable)
        Mail::to($request->email)->send(new SendPasswordResetCode($code));

        return response()->json([
            'message' => 'Se existir uma conta com este e-mail, um código de redefinição foi enviado.'
        ]);
    }

    /**
     * Reseta a senha do usuário usando o código de verificação.
     *
     * @group 1. Autenticação
     *
     * @bodyParam email string required Email do usuário.
     * @bodyParam token string required O código de 6 dígitos recebido por e-mail.
     * @bodyParam password string required A nova senha. Mínimo 8 caracteres.
     * @bodyParam password_confirmation string required Confirmação da nova senha.
     *
     * @response 200 {
     * "message": "Senha redefinida com sucesso!"
     * }
     * @response 400 {
     * "error": "Código de redefinição inválido ou expirado."
     * }
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetRecord || Carbon::parse($resetRecord->created_at)->addMinutes(10)->isPast() || !Hash::check($request->token, $resetRecord->token)) {
            return response()->json(['error' => 'Código de redefinição inválido ou expirado.'], 400);
        }

        $user = User::where('email', $request->email)->first();

        $user->update([
            'password' => Hash::make($request->password)
        ]);
    
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Senha redefinida com sucesso!']);
    }
}
