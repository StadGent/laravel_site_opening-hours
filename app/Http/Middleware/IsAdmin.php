<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use \Illuminate\Auth\AuthenticationException;

class IsAdmin
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
        if ($request->user('api')->cannot('administer', User::class)) {
            throw new AuthenticationException("Unauthorized as admin");
        }

        return $next($request);
    }
}
