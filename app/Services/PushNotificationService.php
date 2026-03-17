<?php

namespace App\Services;

use App\Models\User;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private const SCOPES = ['https://www.googleapis.com/auth/firebase.messaging'];
    private const TOKEN_CACHE_KEY = 'fcm_oauth_token';

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        if (empty($user->fcm_token)) {
            return;
        }

        $projectId = config('services.firebase.project_id');
        if (!$projectId) {
            Log::error('FCM: FIREBASE_PROJECT_ID não configurado');
            return;
        }

        try {
            $token = $this->getOAuthToken();

            $response = Http::withToken($token)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token'        => $user->fcm_token,
                        'notification' => [
                            'title' => $title,
                            'body'  => $body,
                        ],
                        'data' => array_map('strval', $data),
                    ],
                ]);

            if ($response->failed()) {
                $errorCode = $response->json('error.details.0.errorCode') ?? $response->json('error.message');

                // Token inválido/expirado — limpa o token do usuário para não tentar novamente
                if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'], true)) {
                    $user->update(['fcm_token' => null]);
                    Log::info('FCM: Token inválido removido', ['user_id' => $user->id]);
                    return;
                }

                Log::warning('FCM: Falha ao enviar notificação', [
                    'user_id' => $user->id,
                    'status'  => $response->status(),
                    'error'   => $errorCode,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('FCM: Erro inesperado ao enviar notificação', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    private function getOAuthToken(): string
    {
        // Cache do token por 55 minutos (tokens Google expiram em 60 min)
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(55), function () {
            $credentialsPath = config('services.firebase.credentials');

            if (!$credentialsPath || !file_exists($credentialsPath)) {
                throw new \RuntimeException('FCM: Arquivo de credenciais Firebase não encontrado em: ' . $credentialsPath);
            }

            $credentials = new ServiceAccountCredentials(
                self::SCOPES,
                json_decode(file_get_contents($credentialsPath), true)
            );

            $token = $credentials->fetchAuthToken();

            if (empty($token['access_token'])) {
                throw new \RuntimeException('FCM: Não foi possível obter o token OAuth do Google');
            }

            return $token['access_token'];
        });
    }
}
