<?php

namespace App\Http\Middleware;

use App\Models\Calendar;
use App\Models\Channel;
use App\Models\Openinghours;
use App\Models\Service;
use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class HasRoleInService
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->user('api')->hasRole('Admin')) {
            return $next($request);
        }

        $requestServiceId = $this->findTheServiceInTheRequest($request);
        $allowedServices = $request->user('api')->services();

        if ($allowedServices->where('id', $requestServiceId)->count()) {
            return $next($request);
        }

        throw new AuthenticationException("Unauthorized");
    }

    /**
     * @param Request $request
     */
    protected function findTheServiceInTheRequest(Request $request)
    {
        switch (true) {
            case isset($request->calendar):
                $calendar = $request->calendar;
                if (!($request->calendar instanceof Calendar)) {
                    $calendar = Calendar::find($calendar);
                }

                return $calendar->openinghours->channel->service->id;
            case isset($request->openinghours):
                $openinghours = $request->openinghours;
                if (!($request->openinghours instanceof Openinghours)) {
                    $openinghours = Openinghours::find($openinghours);
                }

                return $openinghours->channel->service->id;
            case isset($request->channel):
                $channel = $request->channel;
                if (!($request->channel instanceof Channel)) {
                    $channel = Channel::find($channel);
                }

                return $channel->service->id;
            case isset($request->service):
                $service = $request->service;
                if (!($request->service instanceof Service)) {
                    $service = Service::find($service);
                }

                return $service->id;
            case isset($request->service_id):
                return $request->service_id;
            default:
                throw new AuthenticationException("Unauthorized");
        }
    }
}
