<?php

namespace App\Events;

use App\Models\Friend;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendRequestSent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Friend $friendship
    ) {}
}
