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
     * @param $excludeEditor
     *
     * @return mixed
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, $excludeEditor = false)
    {
        if ($request->user('api')->hasRole('Admin')) {
            return $next($request);
        }

        if (!$excludeEditor && $request->user('api')->hasRole('Editor')) {
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
     * Find the id of the service the request acts on
     *
     * The resource in the URL always decides: route parameters are read with
     * $request->route() only, because $request->calendar and the like return
     * a query string or body value of the same name before the route
     * parameter. The body is only consulted when the URL names no resource,
     * i.e. when creating one (or managing roles and invites).
     *
     * @param Request $request
     * @return int|string
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function findTheServiceInTheRequest(Request $request)
    {
        $serviceId = $this->findTheServiceInTheRoute($request)
            ?? $this->findTheServiceInTheInput($request);

        if (empty($serviceId)) {
            throw new AuthenticationException("Unauthorized");
        }

        return $serviceId;
    }

    /**
     * @param Request $request
     * @return int|null
     */
    private function findTheServiceInTheRoute(Request $request)
    {
        if ($request->route('calendar') !== null) {
            $calendar = $this->resolve(Calendar::class, $request->route('calendar'));

            return $calendar?->openinghours?->channel?->service?->id;
        }
        if ($request->route('openinghours') !== null) {
            $openinghours = $this->resolve(Openinghours::class, $request->route('openinghours'));

            return $openinghours?->channel?->service?->id;
        }
        if ($request->route('channel') !== null) {
            $channel = $this->resolve(Channel::class, $request->route('channel'));

            return $channel?->service?->id;
        }
        if ($request->route('service') !== null) {
            return $this->resolve(Service::class, $request->route('service'))?->id;
        }

        return null;
    }

    /**
     * @param Request $request
     * @return int|string|null
     */
    private function findTheServiceInTheInput(Request $request)
    {
        if ($request->input('openinghours_id') !== null) {
            return Openinghours::find($request->input('openinghours_id'))?->channel?->service?->id;
        }
        if ($request->input('channel_id') !== null) {
            return Channel::find($request->input('channel_id'))?->service?->id;
        }

        return $request->input('service_id');
    }

    /**
     * @param string $class
     * @param mixed $value A bound model or its id
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    private function resolve(string $class, $value)
    {
        return $value instanceof $class ? $value : $class::find($value);
    }
}
