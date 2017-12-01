<?php

namespace App\Providers;

use App\Models\Calendar;
use App\Models\Channel;
use App\Models\Openinghours;
use App\Observers\CalendarObserver;
use App\Observers\ChannelObserver;
use App\Observers\OpeninghoursObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        /* OBSERVERS */
        Calendar::observe(CalendarObserver::class);
        Channel::observe(ChannelObserver::class);
        Openinghours::observe(OpeninghoursObserver::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        /* REPOSITORIES **/
        $this->app->bind('ServicesRepository', function ($app) {
            return new \App\Repositories\ServicesRepository(
                new \App\Models\Service()
            );
        });

        $this->app->bind('CalendarRepository', function ($app) {
            return new \App\Repositories\CalendarRepository(
                new \App\Models\Calendar()
            );
        });

        $this->app->bind('ChannelRepository', function ($app) {
            return new \App\Repositories\ChannelRepository(
                new \App\Models\Channel()
            );
        });

        $this->app->bind('OpeninghoursRepository', function ($app) {
            return new \App\Repositories\OpeninghoursRepository(
                new \App\Models\Openinghours()
            );
        });

        $this->app->bind('EventRepository', function ($app) {
            return new \App\Repositories\EventRepository(
                new \App\Models\Event()
            );
        });

        $this->app->bind('UserRepository', function ($app) {
            return new \App\Repositories\UserRepository(
                new \App\Models\User()
            );
        });

        /* SERVICES **/
        $this->app->singleton(\App\Services\ChannelService::class, function ($app) {
            return \App\Services\ChannelService::getInstance();
        });
        $this->app->alias(\App\Services\ChannelService::class, 'ChannelService');

        $this->app->singleton(\App\Services\SparqlService::class, function ($app) {
            return \App\Services\SparqlService::getInstance();
        });
        $this->app->alias(\App\Services\SparqlService::class, 'SparqlService');

        $this->app->singleton(\App\Services\VestaService::class, function ($app) {
            return \App\Services\VestaService::getInstance();
        });
        $this->app->alias(\App\Services\VestaService::class, 'VestaService');

        $this->app->singleton(\App\Services\LocaleService::class, function ($app) {
            return \App\Services\LocaleService::getInstance();
        });
        $this->app->alias(\App\Services\LocaleService::class, 'LocaleService');

        $this->app->singleton(\App\Services\UserService::class, function ($app) {
            return \App\Services\UserService::getInstance();
        });
        $this->app->alias(\App\Services\UserService::class, 'UserService');

        $this->app->singleton(\App\Services\RecurringOHService::class, function ($app) {
            return \App\Services\RecurringOHService::getInstance();
        });
        $this->app->alias(\App\Services\RecurringOHService::class, 'RecurringOHService');
    }
}
