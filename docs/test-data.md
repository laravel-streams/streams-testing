---
title: Test Data
nav_title: Test Data
description: Sample streams and test data included in the package.
section: packages
package: testing
order: 30
tags: [testing, test, data]
status: ready
---

Every test starts from the same Star Wars streams. The canonical files live in `fixtures/streams`. Each test run copies them into a temporary application and restores that copy afterward.

## Streams

| Stream | Key | What it is |
| --- | --- | --- |
| `films` | `episode_id` | 7 films. Episode 4 is "A New Hope". |
| `people` | `person_id` | Characters. |
| `planets` | `planet_id` | Planets. |
| `species` | `species_id` | Species. |
| `starships` | `starship_id` | Starships. |
| `vehicles` | `vehicle_id` | Vehicles. |
| `files` | `id` | File examples. The id field is a uuid. |

```php
$hope = Streams::entries('films')->find(4);

$this->assertSame('A New Hope', $hope->title);
$this->assertSame('George Lucas', $hope->director);
```

`films.characters` stores `person_id` values from `people`. The other list fields point at `planets`, `starships`, `vehicles`, and `species` the same way.

## Restore

You do not copy files yourself. `TestCase` restores `fixtures/streams` before and after each test, so a created film disappears for the next test.

Call `$this->restoreStreams()` only when one test needs the original data again before it finishes.

## A stream that exists for one test

```php
$this->defineStream('orders', [
    'fields' => [
        ['handle' => 'name', 'type' => 'string'],
    ],
]);

$this->createEntry('orders', [
    'id' => 'alpha',
    'name' => 'Alpha',
]);
```

`defineStream()` registers the stream in memory and stores entries as files inside the temporary application. File fixtures such as `films` stay on the file adapter. Use [migrateStream()](/docs/testing/writing-tests) when the test needs a database table.

Next: [Writing Tests](/docs/testing/writing-tests).
