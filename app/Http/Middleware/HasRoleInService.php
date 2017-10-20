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
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
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
    private function findTheServiceInTheRequest(Request $request)
    {
        switch (true) {
            case isset($request->calendar):
                return $request->calendar->openinghours->channel->service->id;
            case isset($request->openinghours):
                return $request->openinghours->channel->service->id;
            case isset($request->channel):
                return $request->channel->service->id;
            case isset($request->service):
                return $request->service->id;

            default:
                throw new AuthenticationException("Unauthorized");
        }
    }
}
