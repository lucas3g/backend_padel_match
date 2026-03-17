<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        private readonly User $user,
        private readonly string $title,
        private readonly string $body,
        private readonly array $data = []
    ) {
        $this->onQueue('notifications');
    }

    public function handle(PushNotificationService $service): void
    {
        $service->sendToUser($this->user, $this->title, $this->body, $this->data);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FCM Job: Todas as tentativas esgotadas', [
            'user_id' => $this->user->id,
            'title'   => $this->title,
            'error'   => $exception->getMessage(),
        ]);
    }
}
