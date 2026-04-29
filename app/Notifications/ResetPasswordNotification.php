<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Redefinição de Senha — PadelMatch')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Use o código abaixo para redefinir sua senha no aplicativo:')
            ->line('')
            ->line("# {$this->code}")
            ->line('')
            ->line('O código é válido por **30 minutos**.')
            ->line('Se você não solicitou a redefinição de senha, ignore este e-mail.')
            ->salutation('Padel Match');
    }
}
