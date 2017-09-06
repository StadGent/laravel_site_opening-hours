<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\OpeninghoursCreated' => [
            'App\Listeners\HandleNewOpeninghours',
        ],
        'App\Events\OpeninghoursUpdated' => [
            'App\Listeners\HandleUpdatedOpeninghours',
        ],
        'App\Events\CalendarUpdated' => [
            'App\Listeners\HandleUpdatedCalendar',
        ],
        'App\Events\OpeninghoursDeleted' => [
            'App\Listeners\HandleDeletedOpeninghours'
        ],
        'App\Events\ChannelDeleted' => [
            'App\Listeners\HandleDeletedChannel'
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
