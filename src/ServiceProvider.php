<?php

namespace Edalzell\Feeds;

use Statamic\Providers\AddonServiceProvider;

class ServiceProvider extends AddonServiceProvider
{
    protected $routes = [
        'web' => __DIR__.'/routes/web.php',
    ];
}
