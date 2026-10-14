<?php

use Illuminate\Support\ServiceProvider;
use Streams\Core\StreamsServiceProvider;
use Streams\Testing\TestServiceProvider;

return [

    'name' => env('APP_NAME', 'Streams'),

    'env' => env('APP_ENV', 'testing'),

    'debug' => (bool) env('APP_DEBUG', true),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => 'UTC',

    'locale' => 'en',

    'fallback_locale' => 'en',

    // Test fixture, not a secret. Used only when APP_KEY is unset.
    'key' => env('APP_KEY') ?: 'base64:aiGINJ0oFnqrMGUwJYWJuhe6meZoW+GqppwDJD4YZeM=',

    'cipher' => 'AES-256-CBC',

    'providers' => ServiceProvider::defaultProviders()->merge([
        StreamsServiceProvider::class,
        TestServiceProvider::class,
    ])->toArray(),

    'aliases' => [],

];
