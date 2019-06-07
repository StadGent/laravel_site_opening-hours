<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Session\TokenMismatchException;

class VerifyCsrfToken extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        try {
            return parent::handle($request, $next);
        } catch (TokenMismatchException $ex) {
            if ($request->expectsJson()) {
                return response()->json([
                    'csrf' => true,
                    'token' => csrf_token(),
                    'message' => 'The page was open for too long and this triggered our security protection. (csrf)'
                ], 452);
            }
            throw $ex;
        }
    }
}
