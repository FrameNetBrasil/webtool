<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateMaster;
use App\Http\Middleware\Data;
use App\Http\Middleware\Session;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
    //web: __DIR__.'/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
//        $middleware->append(Data::class);
        $middleware->group('web', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            Session::class,
        ]);
        $middleware->group('auth', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            Session::class,
            Authenticate::class,
        ]);
        $middleware->appendToGroup('master', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            Session::class,
            AuthenticateMaster::class
        ]);
        $middleware->appendToGroup('admin', [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            Session::class,
            AuthenticateAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
//        $exceptions->renderable(function (\Illuminate\Validation\ValidationException $e) {
//
//            debug($e->getMessage());
//            $request = request();
//            if ($request->header('HX-Request')) {
//                debug('is htmx', $e->redirectTo);
//                return response('')
//                    ->withHeaders([
//                        'HX-Redirect' => $e->redirectTo
//                    ]);
//            }
//        });
    })->create();
