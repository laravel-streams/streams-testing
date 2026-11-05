<?php

use Illuminate\Foundation\Application;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Testbench 9+ loads this file and sets $APP_BASE_PATH to the Streams
| testing runtime. Return that application. Do not boot a second one.
|
*/

$basePath = $APP_BASE_PATH ?? dirname(__DIR__);

if (! method_exists(Application::class, 'configure')) {
    return new Application($basePath);
}

return Application::configure(basePath: $basePath)->create();
