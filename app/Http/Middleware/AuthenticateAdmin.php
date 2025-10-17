<?php

namespace App\Http\Middleware;

use App\Exceptions\AuthenticateException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->authenticate($request);

        return $next($request);
    }

    protected function authenticate($request)
    {
        if (Auth::check()) {
            if (session('isAdmin')) {
                return true;
            } else {
                $this->unauthorizated($request);
            }
        }
        $this->unauthenticated($request);
    }

    protected function unauthenticated($request)
    {
        throw new AuthenticateException('User is not authenticated. Please, login to access this page.', 401);
    }

    protected function unauthorizated($request)
    {
        throw new AuthenticateException('You don`t have access to this page. Please, login again.', 401);
    }
}
