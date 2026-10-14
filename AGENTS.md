# Streams Testing

This package is the test harness for Laravel Streams addons (`streams/core`, `streams/ui`, `streams/api`, and third-party packages).

## Write a package test

Extend `Streams\Testing\TestCase`. Register the package provider. The harness boots Streams core, serves the Star Wars fixtures from `fixtures/streams`, and restores that copy after every test.

```php
protected function getPackageProviders($app)
{
    return $this->streamsPackageProviders([
        \Vendor\Package\PackageServiceProvider::class,
    ]);
}
```

`streamsPackageProviders()` also includes providers listed under `extra.laravel.providers` in the package being tested. Pass Livewire or other dependencies in the array when the package needs them booted first.

## What a test can rely on

- Sample streams: `films`, `people`, `planets`, `species`, `starships`, `vehicles`, `files`.
- `films` has 7 entries. The key is `episode_id`. Episode 4 is "A New Hope". `people` is keyed by `person_id`.
- The application base path is a temporary Testbench skeleton. Creating, updating, or deleting entries does not change this repository.
- Database defaults to the `testing` connection (SQLite `:memory:`) unless `DB_CONNECTION` is set.
- Mail defaults to the `array` transport unless `MAIL_MAILER` is set.
- The addon under test is available through `Addons::make('vendor/package')` when its Composer package type is `streams-addon`.

## Helpers

Query with core: `Streams::entries('films')->get()` and `Streams::films()`.

- `$this->stream('films')`, `$this->entries('films')`, `$this->entry('films', 4)`
- `$this->createEntry('films', [...])`
- `$this->fakeEntry('films', [...])` saves `Streams::factory()->state()->create()` after filling array-of-relationship fields from the related stream
- `$this->fakeAttributes('films', [...])` for the same payload without saving
- `$this->defineStream('orders', [...])` for a stream that exists only in that test
- `$this->migrateStream('films')` and `$this->migrateStreams()` for database tables
- `$this->getStream()`, `postStream()`, `putStream()`, `patchStream()`, `deleteStream()` — named routes first, otherwise `URL::streams()`
- `$this->assertStreamExists()`, `$this->assertEntryCount()`, `$this->assertEntryExists()`, `$this->assertEntryMissing()`

## Pest

```php
uses(\Streams\Testing\TestCase::class)->in('Feature');
```

## Do not

- Write into `fixtures/streams`. That tree is the pristine data copied into the temp app.
- Point tests at MySQL unless the test is specifically for MySQL. Set `DB_CONNECTION` when that is intentional.
- Rebuild `entries()->get()` or a second factory. Core already has both.
- Assume package discovery is enabled. Testbench discovery is off. Register providers through `streamsPackageProviders()`.
