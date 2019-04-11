<?php

namespace App\Http\Middleware;

use Closure;
use \Illuminate\Auth\AuthenticationException;

class IsEditor
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
        if (!$request->user('api')->hasRole('Editor')) {
            throw new AuthenticationException("Unauthorized as editor");
        }
        return $next($request);
    }
}
