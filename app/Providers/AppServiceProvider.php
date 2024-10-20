<?php

namespace App\Providers;

use App\Data\ScormConfigData;
use App\Data\ScormConfigWithMetadataData;
use App\DriverManagers\SchemaDriverManager;
use App\Scormer;
use Illuminate\Support\ServiceProvider;
use LaravelZero\Framework\Application;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        /**
         * @param  array<string, ScormConfigData|ScormConfigWithMetadataData>  $params
         */
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
