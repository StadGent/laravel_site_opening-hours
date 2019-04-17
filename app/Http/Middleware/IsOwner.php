<?php

namespace App\Http\Middleware;

use App\Models\Role;
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
        if ($request->user('api')->hasRole('Admin')) {
            return $next($request);
        }

        $requestServiceId = $this->findTheServiceInTheRequest($request);
        $allowedServices = $request->user('api')
          ->services()
          ->wherePivot('role_id', Role::where('Name', 'Owner')->first()->id);

        if ($allowedServices
          ->where('id', $requestServiceId)
          ->count()) {
            return $next($request);
        }

        throw new AuthenticationException("Unauthorized");
    }
}
