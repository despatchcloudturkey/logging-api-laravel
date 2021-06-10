<?php

namespace DespatchCloud\LoggingAPI\Providers;

use DespatchCloud\LoggingAPI\Facades\LoggingAPI;
use DespatchCloud\LoggingAPI\LoggingAPIClient;
use Illuminate\Support\ServiceProvider;

class LoggingAPIServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->configure();

        $this->registerAliases();
        $this->registerLoggingAPI();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Setup the configuration for Logging API.
     *
     * @return void
     */
    protected function configure()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/logging-api.php', 'logging-api'
        );
    }

    /**
     * Bind some aliases.
     *
     * @return void
     */
    protected function registerAliases()
    {
        $this->app->alias('LoggingAPI', LoggingAPI::class);
    }

    /**
     * Register the bindings for the JSON Web Token provider.
     *
     * @return void
     */
    protected function registerLoggingAPI()
    {
        $this->app->singleton('logging-api', function ($app) {
            return new LoggingAPIClient(
                $app['config']['logging-api']['publicUrl'],
                $app['config']['logging-api']['apiKey'],
                $app['config']['logging-api']['privateUrl']
            );
        });
    }
}
