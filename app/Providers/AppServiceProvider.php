<?php

namespace App\Providers;

use App\DriverManagers\SchemaDriverManager;
use App\Scormer;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->app->bind(Scormer::class, function (Application $app, array $params) {
            return new Scormer(
                config: $params['config'],
                scormSchemaManager: $app->make(SchemaDriverManager::class)
                    ->driver(
                        $params['config']->version->getDriver()
                    )
            );
        });
    }
}
