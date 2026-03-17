<?php

namespace App\Events;

use App\Models\GameInvitation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameInvitationSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly GameInvitation $invitation
    ) {}
}
