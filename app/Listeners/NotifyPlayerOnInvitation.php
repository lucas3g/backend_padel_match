<?php

namespace App\Listeners;

use App\Events\GameInvitationSent;
use App\Jobs\SendPushNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyPlayerOnInvitation implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(GameInvitationSent $event): void
    {
        $invitation = $event->invitation;
        $invitation->loadMissing(['player.user', 'invitedBy', 'game']);

        $invitedUser = $invitation->player?->user;
        if (!$invitedUser) {
            return;
        }

        $inviterName = $invitation->invitedBy?->full_name ?? 'Um jogador';
        $gameTitle   = $invitation->game?->title ?? 'uma partida';

        SendPushNotification::dispatch(
            $invitedUser,
            'Convite para partida!',
            "{$inviterName} te convidou para {$gameTitle}.",
            [
                'type'          => 'game_invitation',
                'game_id'       => (string) $invitation->game_id,
                'invitation_id' => (string) $invitation->id,
            ]
        );
    }
}
