<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

collect(config('feeds.types', []))->each(function ($feed, $key) {
    Route::get($feed['route'], Str::title($key));
});
