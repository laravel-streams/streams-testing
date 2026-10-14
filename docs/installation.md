---
title: 'Testing: Installation'
nav_title: Installation
description: Install and configure the Streams Testing package.
section: packages
package: testing
order: 20
tags: [testing, installation]
status: ready
---

## Requirements

- PHP 8.1 or newer. Laravel 11 and 12 need PHP 8.2.
- Laravel 10, 11, or 12, through Orchestra Testbench 8, 9, or 10.
- `streams/core` ^2.0. Laravel 11 and 12 also need core to allow `symfony/yaml` ^7.

## Install

```bash
composer require --dev streams/testing:1.0.x-dev
```

## phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Tests">
            <directory suffix="Test.php">./tests</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_KEY" value="base64:aiGINJ0oFnqrMGUwJYWJuhe6meZoW+GqppwDJD4YZeM="/>
    </php>
</phpunit>
```

The `APP_KEY` value is a test fixture, not a secret. The runtime uses it only when `APP_KEY` is unset. Leave `DB_CONNECTION` unset to use SQLite in memory. Set it only when a test must use another database.

## First test

Register the addon provider. `streamsPackageProviders()` already includes Streams core and any providers listed in the package's `extra.laravel.providers`.

```php
<?php

namespace Tests;

use Streams\Core\Support\Facades\Streams;

class ExampleTest extends \Streams\Testing\TestCase
{
    protected function getPackageProviders($app)
    {
        return $this->streamsPackageProviders([
            \Vendor\Package\PackageServiceProvider::class,
        ]);
    }

    public function test_films_are_loaded()
    {
        $this->assertSame(7, Streams::entries('films')->count());
    }
}
```

Put `Tests\` in `autoload-dev`, run `composer dump-autoload`, then:

```bash
vendor/bin/phpunit
```

Pest can use the same case:

```php
uses(\Streams\Testing\TestCase::class)->in('Feature');
```

Next: [Test Data](/docs/testing/test-data) and [Writing Tests](/docs/testing/writing-tests).
