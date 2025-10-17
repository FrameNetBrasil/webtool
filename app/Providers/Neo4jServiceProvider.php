<?php

namespace App\Providers;

use App\Services\Neo4j\ConnectionService;
use Illuminate\Support\ServiceProvider;
use Laudis\Neo4j\Contracts\ClientInterface;

class Neo4jServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ClientInterface::class, function () {
            return ConnectionService::connection();
        });

        $this->app->alias(ClientInterface::class, 'neo4j');
    }

    public function boot(): void
    {
        //
    }
}