<?php

namespace App\Providers;

use App\Models\Calendar;
use App\Models\Channel;
use App\Models\Event;
use App\Models\Openinghours;
use App\Models\Service;
use App\Models\User;
use App\Observers\CalendarObserver;
use App\Observers\ChannelObserver;
use App\Observers\OpeninghoursObserver;
use App\Observers\ServiceObserver;
use App\Repositories\CalendarRepository;
use App\Repositories\ChannelRepository;
use App\Repositories\EventRepository;
use App\Repositories\OpeninghoursRepository;
use App\Repositories\ServicesRepository;
use App\Repositories\UserRepository;
use App\Services\ChannelService;
use App\Services\LocaleService;
use App\Services\QueueService;
use App\Services\RecurringOHService;
use App\Services\ServiceService;
use App\Services\SparqlService;
use App\Services\UserService;
use App\Services\VestaService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

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
        Passport::enablePasswordGrant();
        Service::observe(ServiceObserver::class);
        Calendar::observe(CalendarObserver::class);
        Channel::observe(ChannelObserver::class);
        Openinghours::observe(OpeninghoursObserver::class);
        Schema::defaultStringLength(191);
        Passport::withCookieSerialization();
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
            return new ServicesRepository(
                new Service()
            );
        });

        $this->app->bind('CalendarRepository', function ($app) {
            return new CalendarRepository(
                new Calendar()
            );
        });

        $this->app->bind('ChannelRepository', function ($app) {
            return new ChannelRepository(
                new Channel()
            );
        });

        $this->app->bind('OpeninghoursRepository', function ($app) {
            return new OpeninghoursRepository(
                new Openinghours()
            );
        });

        $this->app->bind('EventRepository', function ($app) {
            return new EventRepository(
                new Event()
            );
        });

        $this->app->bind('UserRepository', function ($app) {
            return new UserRepository(
                new User()
            );
        });

        /* SERVICES **/
        $this->app->singleton(ServiceService::class, function ($app) {
            return ServiceService::getInstance();
        });
        $this->app->alias(ServiceService::class, 'ServiceService');

        $this->app->singleton(ChannelService::class, function ($app) {
            return ChannelService::getInstance();
        });
        $this->app->alias(ChannelService::class, 'ChannelService');

        $this->app->singleton(SparqlService::class, function ($app) {
            return SparqlService::getInstance();
        });
        $this->app->alias(SparqlService::class, 'SparqlService');

        $this->app->singleton(VestaService::class, function ($app) {
            return VestaService::getInstance();
        });
        $this->app->alias(VestaService::class, 'VestaService');

        $this->app->singleton(LocaleService::class, function ($app) {
            return LocaleService::getInstance();
        });
        $this->app->alias(LocaleService::class, 'LocaleService');

        $this->app->singleton(UserService::class, function ($app) {
            return UserService::getInstance();
        });
        $this->app->alias(UserService::class, 'UserService');

        $this->app->singleton(RecurringOHService::class, function ($app) {
            return RecurringOHService::getInstance();
        });
        $this->app->alias(RecurringOHService::class, 'RecurringOHService');

        $this->app->singleton(QueueService::class, function ($app) {
            return QueueService::getInstance();
        });
        $this->app->alias(QueueService::class, 'QueueService');
    }
}
