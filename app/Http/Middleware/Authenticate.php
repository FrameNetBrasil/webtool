<?php

namespace App\Http\Middleware;

use App\Exceptions\AuthenticateException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Authenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->authenticate($request);

        return $next($request);
    }

    protected function authenticate($request)
    {
        if (Auth::check()) {
            return true;
        }
        $this->unauthenticated($request);
    }

    protected function unauthenticated($request)
    {
        throw new AuthenticateException('User is not authenticated. Please, login to access this page.', 401);
    }
}
