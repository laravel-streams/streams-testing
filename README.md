# Streams Testing

Test harness for Laravel Streams addons. It boots Laravel, loads the sample streams, and restores them after every test.

```bash
composer require streams/testing --dev
vendor/bin/phpunit
```

## Write a test

```php
class FilmTest extends \Streams\Testing\TestCase
{
    protected function getPackageProviders($app)
    {
        return $this->streamsPackageProviders([
            \Vendor\Package\PackageServiceProvider::class,
        ]);
    }

    public function test_films()
    {
        $this->assertSame(7, Streams::entries('films')->count());
        $this->assertSame('A New Hope', $this->entry('films', 4)->title);
    }
}
```

Query with `Streams::entries('films')` or `Streams::films()`. The test case adds helpers around that, it does not replace it.

## What a test can rely on

- Streams: `films`, `people`, `planets`, `species`, `starships`, `vehicles`, `files`.
- `films` has 7 entries. The key is `episode_id`. Episode 4 is "A New Hope". People are keyed by `person_id`.
- The Laravel app is a temporary Testbench skeleton. Fixture edits stay in that directory.
- Database defaults to SQLite `:memory:` on the `testing` connection unless `DB_CONNECTION` is set.
- Mail defaults to the `array` transport unless `MAIL_MAILER` is set.
- `Addons::make('vendor/package')` resolves the package under test when its Composer type is `streams-addon`.

## Helpers

- `$this->fakeEntry('films', [...])` saves a factory-built entry. Array fields of relationships use real related keys.
- `$this->fakeAttributes('films', [...])` builds that payload without saving. `Streams::factory('films')->state([...])->create()` is the unsaved core factory.
- `$this->migrateStream('films')` creates a table from the stream fields. `$this->migrateStreams()` does that only for `database` sources.
- `$this->getStream('films/{id}', ['id' => 4])` and `postStream`, `putStream`, `patchStream`, `deleteStream`. A registered route name wins; otherwise the string is a Streams URI.
- `$this->defineStream()`, `$this->createEntry()`, `$this->assertEntryCount()`, `$this->assertStreamExists()`.

## Docs

- [Introduction](docs/introduction.md)
- [Installation](docs/installation.md)
- [Test Data](docs/test-data.md)
- [Writing Tests](docs/writing-tests.md)
- [Configuration](docs/configuration.md)
- [Troubleshooting](docs/troubleshooting.md)

Published at https://streams.dev/docs/testing/introduction.

## Requirements

- PHP 8.1 or newer (8.2 or newer for Laravel 11 and 12)
- Laravel 10, 11, or 12
- Streams core ^2.0

## License

MIT. See [LICENSE.md](LICENSE.md).
