<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Auth\AuthenticationException;

class IsOwner extends HasRoleInService
{

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param bool $excludeEditor
     *
     * @return mixed
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, $excludeEditor = false)
    {
        // Admins pass even when the request names no service.
        if ($request->user('api')->can('administer', User::class)) {
            return $next($request);
        }

        $requestServiceId = $this->findTheServiceInTheRequest($request);
        if ($request->user('api')->can('ownService', [User::class, $requestServiceId])) {
            return $next($request);
        }

        throw new AuthenticationException("Unauthorized");
    }
}
