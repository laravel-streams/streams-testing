<?php

namespace Streams\Testing;

use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if (! $this->app->bound('addons')) {
            return;
        }

        $composer = RuntimeApplication::rootComposer();
        $name = $composer['name'] ?? null;

        if (($composer['type'] ?? null) !== 'streams-addon' || ! is_string($name)) {
            return;
        }

        $addons = $this->app->make('addons');

        if ($addons->collection()->has($name)) {
            return;
        }

        $addons->load(RuntimeApplication::projectRoot());
    }
}
