<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config("webtool.logSQL") == 'debug') {
            DB::enableQueryLog();
            DB::listen(function ($query) {
                debugQuery($query->sql, $query->bindings);
            });
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::addExtension('js','php');
        Blade::anonymousComponentPath(app_path('UI/components/layout'), 'layout');
        Blade::anonymousComponentPath(app_path('UI/components'), 'ui');
        Blade::anonymousComponentPath(app_path('UI/components/icon'), 'icon');
        Blade::anonymousComponentPath(app_path('UI/components/element'), 'element');
        Blade::anonymousComponentPath(app_path('UI/components/search'), 'search');
        Blade::componentNamespace('App\View\Components\Combobox', 'combobox');
        Blade::componentNamespace('App\View\Components\Checkbox', 'checkbox');
    }
}
