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
        // Ensure Laravel storage directories exist (critical for Docker deployments)
        $this->ensureStorageDirectoriesExist();

        View::addExtension('js','php');
        Blade::anonymousComponentPath(app_path('UI/components/layout'), 'layout');
        Blade::anonymousComponentPath(app_path('UI/components'), 'ui');
        Blade::anonymousComponentPath(app_path('UI/components/icon'), 'icon');
        Blade::anonymousComponentPath(app_path('UI/components/element'), 'element');
        Blade::anonymousComponentPath(app_path('UI/components/search'), 'search');
        Blade::componentNamespace('App\View\Components\Combobox', 'combobox');
        Blade::componentNamespace('App\View\Components\Checkbox', 'checkbox');
    }

    /**
     * Ensure Laravel storage directories exist.
     * This is especially important for Docker deployments where volumes may override built directories.
     */
    protected function ensureStorageDirectoriesExist(): void
    {
        $directories = [
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('framework/testing'),
            storage_path('logs'),
            storage_path('app/public'),
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                @mkdir($directory, 0775, true);
            }
        }
    }
}
