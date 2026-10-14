---
title: 'Testing: Configuration'
nav_title: Configuration
description: Configure the Streams Testing environment.
section: packages
package: testing
order: 50
tags: [testing, configuration]
status: ready
---

The harness picks defaults that keep package tests offline. Override them from `phpunit.xml` or from the test.

## Environment

| Variable | Default when unset | Effect |
| --- | --- | --- |
| `APP_KEY` | Test fixture fallback when unset, not a secret | Encryption works without a project `.env`. |
| `DB_CONNECTION` | `testing` (SQLite `:memory:`) | Set `mysql` only for a test that needs MySQL. |
| `MAIL_MAILER` | `array` | Mail is stored in memory. |

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="APP_KEY" value="base64:aiGINJ0oFnqrMGUwJYWJuhe6meZoW+GqppwDJD4YZeM="/>
    <env name="DB_CONNECTION" value="mysql"/>
    <env name="DB_DATABASE" value="testing"/>
</php>
```

The test application does not load a project `.env` file.

## Providers

Streams core and this package's `TestServiceProvider` are on the application provider list. Addon tests add their own provider with `streamsPackageProviders()`.

Testbench package discovery is off. A provider that is only listed in `extra.laravel.providers` is included when the test calls `streamsPackageProviders()`.

## One-off streams

`defineStream()` registers a stream for the current test. The default source type is `file`, stored in the temporary application.

```php
$this->defineStream('orders', [
    'config' => [
        'source' => [
            'type' => 'database',
            'table' => 'orders',
        ],
        'key_name' => 'id',
    ],
    'fields' => [
        ['handle' => 'id', 'type' => 'integer', 'required' => true],
        ['handle' => 'name', 'type' => 'string'],
    ],
]);

$this->migrateStreams();
```

`migrateStreams()` creates tables for `database` sources only. Call `migrateStream('films')` when you want a table for a file stream without changing its fixture.

## Where the app lives

Each PHP process gets a directory under the system temp path. That directory is a Testbench Laravel skeleton plus:

- `fixtures/streams`, copied to `streams/`
- the sample views (`films`, `film`, `welcome`, and the other entry views)
- `public/vendor/testing` and `storage/app/vendor/testing`

Parallel PHPUnit workers get separate directories. Do not write fixture files into the package checkout.

Next: [Troubleshooting](/docs/testing/troubleshooting).
