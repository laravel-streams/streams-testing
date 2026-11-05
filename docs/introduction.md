---
title: 'Testing: Introduction'
nav_title: Introduction
description: Testing package for Streams with pre-configured environments and sample data.
section: packages
package: testing
order: 10
tags: [testing, introduction]
status: ready
---

Streams Testing is the test harness for Streams addons. It boots a Laravel application, loads the sample streams, and restores that data after every test.

Extend `Streams\Testing\TestCase` and query with the same API you use in the application.

```php
<?php

namespace Tests;

use Streams\Core\Support\Facades\Streams;

class FilmTest extends \Streams\Testing\TestCase
{
    public function test_films_include_a_new_hope()
    {
        $films = Streams::entries('films')->get();

        $this->assertCount(7, $films);
        $this->assertSame('A New Hope', Streams::entries('films')->find(4)->title);
    }
}
```

`Streams::films()` is the same call as `Streams::entries('films')`.

The application lives in a temporary directory. Creating or deleting entries does not change the package files. The database is SQLite in memory unless the test sets `DB_CONNECTION`.

Continue with [Installation](/docs/testing/installation).
