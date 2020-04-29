<?php

namespace Edalzell\Feeds;

use Illuminate\Support\Facades\Route;
use Statamic\Providers\AddonServiceProvider;
use Edalzell\Feeds\Http\Controllers\FeedsController;

class ServiceProvider extends AddonServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    public $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        $this->publishes([
            __DIR__.'/../config.php' => config_path('feeds.php'),
        ]);

        $this->registerWebRoutes(function () {
            collect(config('feeds.types', []))->each(function ($feed, $key) {
                Route::get($feed['route'], [FeedsController::class, $key]);
            });
        });
    }
}
