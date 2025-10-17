<?php

namespace App\Http\Middleware;

use App\Services\AppService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Session
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only handle language setup - authentication is handled by Laravel Auth
        $language = session('currentLanguage') ?? null;
        $idLanguage = $language ? $language->idLanguage : '';
        if ($idLanguage == '') {
            $idLanguage = config('webtool.defaultIdLanguage') ?? '';
            if ($idLanguage == '') {
                $idLanguage = 1;
            }
            AppService::setCurrentLanguage((int)$idLanguage);
        }
        return $next($request);
    }
}
