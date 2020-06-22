<?php

namespace Edalzell\Feeds;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    public function boot()
    {
        parent::boot();

        $this->publishes([
            __DIR__.'/../config.php' => config_path('feeds.php'),
        ]);

        Statamic::booted(function () {
            $this->registerWebRoutes(function () {
                collect(config('feeds.types', []))->each(function ($feed, $key) {
                    Route::namespace('\Edalzell\Feeds\Http\Controllers')
                        ->get($feed['route'], Str::title($key));
                });
            });
        });
    }
}
