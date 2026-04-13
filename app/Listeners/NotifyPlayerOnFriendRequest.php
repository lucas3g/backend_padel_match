<?php

namespace App\Listeners;

use App\Events\FriendRequestSent;
use App\Jobs\SendPushNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyPlayerOnFriendRequest implements ShouldQueue
{
    public string $queue = 'notifications';

    public function handle(FriendRequestSent $event): void
    {
        $friendship = $event->friendship;
        $friendship->loadMissing(['player', 'friend.user']);

        $receiverUser = $friendship->friend?->user;
        if (!$receiverUser) {
            return;
        }

        $senderName = $friendship->player?->full_name ?? 'Um jogador';

        SendPushNotification::dispatch(
            $receiverUser,
            'Nova solicitação de amizade',
            "{$senderName} quer ser seu amigo no PadelMatch.",
            [
                'type'          => 'friend_request',
                'friendship_id' => (string) $friendship->id,
                'player_id'     => (string) $friendship->player_id,
            ]
        );
    }
}
