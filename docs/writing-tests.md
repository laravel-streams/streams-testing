---
title: Writing Tests
nav_title: Writing Tests
description: Write effective tests for Streams applications.
section: packages
package: testing
order: 40
tags: [testing, writing, tests]
status: ready
---

Query with Streams. Use the test helpers when you need a saved fake entry, a database table, or an HTTP call.

## Query

```php
$films = Streams::entries('films')
    ->where('director', 'George Lucas')
    ->orderBy('release_date')
    ->get();

$hope = Streams::films()->find(4);
```

`$this->stream('films')`, `$this->entries('films')`, and `$this->entry('films', 4)` are the same lookups with a clearer failure when the stream or entry is missing.

## Create

Pass the fields you care about. `fakeEntry()` asks Streams' factory to fill the rest, including relationship lists, and saves the entry.

```php
$film = $this->fakeEntry('films', [
    'episode_id' => 99,
    'title' => 'Probe',
]);

$this->assertSame('Probe', $film->title);
$this->assertNotEmpty($film->characters);
```

`$this->fakeAttributes('films', [...])` returns that payload without saving. `$this->createEntry('films', [...])` saves only the attributes you pass.

`Streams::factory('films')->state([...])->create()` is the core factory. It returns an unsaved entry. `fakeEntry()` is that call plus `save()`.

## Database

File streams stay files. `migrateStream()` builds a table from the stream fields. `migrateStreams()` does that only for streams whose source type is `database`.

```php
$this->migrateStream('films');

$this->assertTrue(Schema::hasTable('films'));
```

The table uses the stream id unless you pass a table name or the stream sets `config.source.table`. Integer keys become the primary column. Array, object, and multiselect fields become JSON columns.

## HTTP

`$this->getStream()` calls a named route when one exists, and otherwise treats the string as a Streams URI (`URL::streams()`).

```php
Route::streams('films/{id}', [
    'stream' => 'films',
    'view' => 'film',
]);

$this->getStream('films/{id}', ['id' => 4])->assertSee('A New Hope');
```

`postStream()`, `putStream()`, `patchStream()`, and `deleteStream()` use the same URL. A registered name such as `streams.api.entries.list` wins over URI parsing:

```php
$this->getStream('streams.api.entries.list', [
    'stream' => 'films',
]);
```

## Assertions

- `$this->assertStreamExists('films')` and `$this->assertStreamMissing('orders')`
- `$this->assertEntryCount('films', 7)`
- `$this->assertEntryExists('films', 4)` and `$this->assertEntryMissing('films', 99)`

## Providers

```php
protected function getPackageProviders($app)
{
    return $this->streamsPackageProviders([
        \Livewire\LivewireServiceProvider::class,
        \Vendor\Package\PackageServiceProvider::class,
    ]);
}
```

Put dependencies that must boot first, such as Livewire, at the front of that list.

Next: [Configuration](/docs/testing/configuration).
