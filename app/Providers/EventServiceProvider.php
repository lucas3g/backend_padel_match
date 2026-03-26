<?php

namespace App\Providers;

use App\Events\GameFinalized;
use App\Events\GameInvitationSent;
use App\Events\PlayerJoinedGame;
use App\Events\PlayerLeftGame;
use App\Events\TeamsUpdated;
use App\Listeners\NotifyPlayerOnInvitation;
use App\Listeners\NotifyPlayersOnGameFinalized;
use App\Listeners\NotifyPlayersOnGameJoin;
use App\Listeners\NotifyPlayersOnGameLeave;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        PlayerJoinedGame::class => [
            NotifyPlayersOnGameJoin::class,
        ],
        PlayerLeftGame::class => [
            NotifyPlayersOnGameLeave::class,
        ],
        GameInvitationSent::class => [
            NotifyPlayerOnInvitation::class,
        ],
        GameFinalized::class => [
            NotifyPlayersOnGameFinalized::class,
        ],
        TeamsUpdated::class => [],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
