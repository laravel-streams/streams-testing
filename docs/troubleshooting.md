---
title: Troubleshooting
nav_title: Troubleshooting
description: Common issues when testing with Streams Testing.
section: packages
package: testing
order: 60
tags: [testing, troubleshooting]
status: ready
---

## Class `Streams\Testing\TestCase` was not found

The package is a dev dependency, and Composer's autoload is stale.

```bash
composer require --dev streams/testing:1.0.x-dev
composer dump-autoload
```

Confirm `vendor/bin/phpunit` uses that same `vendor/autoload.php` as `phpunit.xml`.

## Laravel 11 or 12 will not install

Testbench 9 and 10 need Symfony YAML 7. `streams/core` must allow `symfony/yaml: ^6.2|^7.0`. On Laravel 10, Testbench 8 installs with YAML 6.

## A test sees data from the previous test

The test class must extend `Streams\Testing\TestCase`, and `setUp()` / `tearDown()` must call the parent. The parent restores `fixtures/streams` around every test.

`$this->restoreStreams()` puts the original files back in the middle of a single test. Streams already loaded in memory keep their current entries until the next test boots a new application.

## `films` does not have 7 entries

Something in this test created or deleted a film. Assert the count before that call, or split the mutation into its own test. The next test starts from 7 again.

## SQLSTATE or a missing MySQL server

`DB_CONNECTION` is set to `mysql` in `phpunit.xml` or the environment. Remove it to use the `testing` SQLite connection. Keep MySQL only for a test that is specifically about MySQL.

## A route helper returned the wrong URL

`getStream()` uses a named route when `Route::has($name)` is true. Otherwise it treats the string as a Streams URI, so `films/{id}` with `['id' => 4]` becomes `/films/4`.

Register the name before the lookup is built:

```php
Route::get('catalog/{id}', [
    'as' => 'films.catalog',
    'uses' => fn () => 'ok',
]);
```

Calling `->name()` after `Route::get()` does not update the name index until the routes are refreshed.

## An image or view is missing

Sample views are the `films`, `film`, `welcome`, `planets`, and `people` templates copied into the temporary app. Sample images are `public/vendor/testing/img/example.jpg` and `storage/app/vendor/testing/img/example.jpg`.

`Images::make('public/vendor/testing/img/example.jpg')` reads the public file. `local://vendor/testing/img/example.jpg` reads the storage disk.

## The addon provider did not boot

Package discovery is disabled. Return the provider from `getPackageProviders()`:

```php
protected function getPackageProviders($app)
{
    return $this->streamsPackageProviders([
        \Vendor\Package\PackageServiceProvider::class,
    ]);
}
```

Subclasses that replace this method and do not call `streamsPackageProviders()` must list `Streams\Core\StreamsServiceProvider` themselves. The testing application also registers that provider, so most addon tests only add their own.
