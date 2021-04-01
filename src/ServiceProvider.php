<?php

namespace Edalzell\Feeds;

use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'web' => __DIR__.'/routes/web.php',
    ];

    public function boot()
    {
        parent::boot();

        $this->publishes([
            __DIR__.'/../config.php' => config_path('feeds.php'),
        ]);
    }
}
