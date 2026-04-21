<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * @OA\OpenApi(
 *   @OA\Info(
 *     title="Padel Match API",
 *     version="1.0.0",
 *     description="Documentação da API do Padel Match"
 *   )
 * )
 *
 * @OA\Tag(
 *   name="Auth",
 *   description="Autenticação de usuários"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Auth"},
     *     summary="Registrar novo usuário",
     *     description="Cria um novo usuário e envia código de verificação por e-mail",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string", example="Mateus Perego"),
     *             @OA\Property(property="email", type="string", format="email", example="mateus@email.com"),
     *             @OA\Property(property="password", type="string", format="password", example="123456"),
     *             @OA\Property(property="fcm_token", type="string", nullable=true, example="dYr3kExampleToken...")
     *         )
     *     ),
     *
     *     @OA\Response(response=201, description="Usuário criado — verificação de e-mail enviada"),
     *     @OA\Response(response=422, description="Erro de validação")
     * )
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string',
            'email'     => 'required|email|unique:users',
            'password'  => 'required|min:6',
            'fcm_token' => 'nullable|string',
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'fcm_token' => $data['fcm_token'] ?? null,
        ]);

        $user->assignRole('player');
        $user->sendEmailVerificationNotification();

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message'        => 'Usuário criado. Verifique seu e-mail para ativar a conta.',
            'user'           => $user,
            'token'          => $token,
            'email_verified' => false,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Auth"},
     *     summary="Login no sistema",
     *     description="Faz login e retorna token + status de verificação de e-mail",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="mateus@email.com"),
     *             @OA\Property(property="password", type="string", format="password", example="123456")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Dados do usuário autenticado"),
     *     @OA\Response(response=401, description="Credenciais inválidas")
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        $user  = User::where('email', $request->email)->first();
        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user'           => $user,
            'token'          => $token,
            'email_verified' => $user->hasVerifiedEmail(),
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/email/verify",
     *     tags={"Auth"},
     *     summary="Verificar e-mail com código OTP",
     *     description="Confirma o e-mail do usuário usando o código de 6 dígitos recebido",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="482951")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="E-mail verificado com sucesso"),
     *     @OA\Response(response=422, description="Código inválido ou expirado"),
     *     @OA\Response(response=429, description="Muitas tentativas")
     * )
     */
    public function verifyEmail(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'E-mail já verificado.']);
        }

        $rateLimiterKey = 'email_verify:' . $user->id;

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimiterKey);
            return response()->json([
                'message'     => "Muitas tentativas. Tente novamente em {$seconds} segundos.",
                'retry_after' => $seconds,
            ], 429);
        }

        if (! $user->hasValidVerificationCode($request->code)) {
            RateLimiter::hit($rateLimiterKey, 900);
            return response()->json(['message' => 'Código inválido ou expirado.'], 422);
        }

        RateLimiter::clear($rateLimiterKey);
        $user->markEmailAsVerified();

        return response()->json(['message' => 'E-mail verificado com sucesso.']);
    }

    /**
     * @OA\Post(
     *     path="/api/email/resend",
     *     tags={"Auth"},
     *     summary="Reenviar código de verificação",
     *     description="Gera e envia um novo código de verificação por e-mail",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Código reenviado"),
     *     @OA\Response(response=429, description="Limite de reenvios atingido")
     * )
     */
    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'E-mail já verificado.']);
        }

        $rateLimiterKey = 'email_resend:' . $user->id;

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimiterKey);
            return response()->json([
                'message'     => "Limite de reenvios atingido. Tente novamente em {$seconds} segundos.",
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($rateLimiterKey, 3600);
        $user->sendEmailVerificationNotification();

        return response()->json(['message' => 'Código de verificação reenviado para ' . $user->email . '.']);
    }

    /**
     * @OA\Post(
     *     path="/api/password/forgot",
     *     tags={"Auth"},
     *     summary="Solicitar redefinição de senha",
     *     description="Envia um código OTP de 6 dígitos para o e-mail informado",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="mateus@email.com")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Código enviado (resposta idêntica mesmo se e-mail não existir)"),
     *     @OA\Response(response=429, description="Muitas solicitações")
     * )
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $rateLimiterKey = 'pwd_forgot:' . $request->ip();

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimiterKey);
            return response()->json([
                'message'     => "Muitas solicitações. Tente novamente em {$seconds} segundos.",
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($rateLimiterKey, 3600);

        // Busca silenciosa: não revela se o e-mail existe
        $user = User::where('email', $request->email)->first();
        $user?->sendPasswordResetCode();

        return response()->json([
            'message' => 'Se este e-mail estiver cadastrado, você receberá um código em instantes.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/password/reset",
     *     tags={"Auth"},
     *     summary="Redefinir senha com código OTP",
     *     description="Valida o código e troca a senha. Revoga todos os tokens ativos do usuário.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","code","password","password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email", example="mateus@email.com"),
     *             @OA\Property(property="code", type="string", example="482951"),
     *             @OA\Property(property="password", type="string", example="novaSenha123"),
     *             @OA\Property(property="password_confirmation", type="string", example="novaSenha123")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="Senha redefinida com sucesso"),
     *     @OA\Response(response=422, description="Código inválido, expirado ou dados incorretos"),
     *     @OA\Response(response=429, description="Muitas tentativas")
     * )
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'code'                  => 'required|string|size:6',
            'password'              => 'required|min:6|confirmed',
        ]);

        $rateLimiterKey = 'pwd_reset:' . $request->email;

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimiterKey);
            return response()->json([
                'message'     => "Muitas tentativas. Tente novamente em {$seconds} segundos.",
                'retry_after' => $seconds,
            ], 429);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! $user->hasValidPasswordResetCode($request->code)) {
            RateLimiter::hit($rateLimiterKey, 900);
            return response()->json(['message' => 'Código inválido ou expirado.'], 422);
        }

        RateLimiter::clear($rateLimiterKey);

        $user->forceFill([
            'password'                  => Hash::make($request->password),
            'password_reset_code'       => null,
            'password_reset_expires_at' => null,
        ])->save();

        // Revoga todos os tokens — força novo login em todos os dispositivos
        $user->tokens()->delete();

        return response()->json(['message' => 'Senha redefinida com sucesso. Faça login novamente.']);
    }

    /**
     * @OA\Get(
     *     path="/api/me",
     *     tags={"Auth"},
     *     summary="Dados do usuário autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Dados do usuário autenticado"),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $user->load('player');

        return response()->json($user);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth"},
     *     summary="Logout do usuário autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Response(response=200, description="Logout realizado com sucesso"),
     *     @OA\Response(response=401, description="Usuário não autenticado")
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado'], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/user/fcm-token",
     *     tags={"Auth"},
     *     summary="Atualiza o FCM token do usuário autenticado",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"fcm_token"},
     *             @OA\Property(property="fcm_token", type="string", example="dYr3kExampleToken...")
     *         )
     *     ),
     *
     *     @OA\Response(response=200, description="FCM token atualizado com sucesso"),
     *     @OA\Response(response=401, description="Não autenticado"),
     *     @OA\Response(response=422, description="Dados inválidos")
     * )
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate(['fcm_token' => 'required|string']);

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['message' => 'FCM token atualizado com sucesso'], 200);
    }
}
