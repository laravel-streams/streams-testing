<?php

namespace Streams\Testing\Concerns;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Spatie\Html\HtmlServiceProvider;
use Streams\Core\Criteria\Criteria;
use Streams\Core\Entry\Contract\EntryInterface;
use Streams\Core\Field\Field;
use Streams\Core\Stream\Stream;
use Streams\Core\StreamsServiceProvider;
use Streams\Core\Support\Facades\Streams;
use Streams\Testing\RuntimeApplication;
use Streams\Testing\Schema\StreamMigrator;

trait InteractsWithStreams
{
    protected function stream(string $id): Stream
    {
        $this->assertStreamExists($id);

        return Streams::make($id);
    }

    protected function entries(string $id): Criteria
    {
        $this->assertStreamExists($id);

        return Streams::entries($id);
    }

    protected function entry(string $stream, mixed $key): EntryInterface
    {
        $entry = Streams::repository($stream)->find($key);

        $this->assertNotNull($entry, "Entry [{$key}] was not found in stream [{$stream}].");

        return $entry;
    }

    protected function createEntry(string $stream, array $attributes = []): EntryInterface
    {
        return $this->entries($stream)->create($attributes);
    }

    /**
     * Build an entry with Streams' factory, fill structured array items, and save it.
     */
    protected function fakeEntry(string $stream, array $overrides = []): EntryInterface
    {
        $entry = $this->fabricate($stream, $overrides);

        $entry->save();

        return $entry;
    }

    protected function fakeAttributes(string $stream, array $overrides = []): array
    {
        return array_replace($this->fabricate($stream, $overrides)->getAttributes(), $overrides);
    }

    protected function fabricate(string $stream, array $overrides = []): EntryInterface
    {
        $definition = $this->stream($stream);

        $entry = Streams::factory($stream)->state($overrides)->create();

        $this->fillStructuredFields($definition, $entry, $overrides);

        return $entry;
    }

    protected function fillStructuredFields(Stream $stream, EntryInterface $entry, array $overrides): void
    {
        foreach ($stream->fields as $field) {
            if (array_key_exists($field->handle, $overrides) || $field->type !== 'array') {
                continue;
            }

            $items = $field->config('items', []);

            if (! is_array($items) || ! isset($items[0]['type'])) {
                continue;
            }

            $entry->setAttribute($field->handle, $this->generateArrayValue($items[0]));
        }
    }

    protected function generateArrayValue(array $definition): array
    {
        $type = $definition['type'] ?? 'string';
        $config = is_array($definition['config'] ?? null) ? $definition['config'] : [];

        if ($type === 'relationship') {
            return $this->relatedKeys($config['related'] ?? null, 3);
        }

        $value = $this->generateItem($type, $config, $definition['handle'] ?? 'item');

        return $value === null ? [] : [$value];
    }

    protected function generateItem(string $type, array $config, string $handle): mixed
    {
        $class = Config::get('streams.core.field_types.'.$type);

        if (! is_string($class) || ! class_exists($class)) {
            return null;
        }

        $field = new $class([
            'handle' => $handle,
            'type' => $type,
            'config' => $config,
        ]);

        return $field instanceof Field ? $field->generate() : null;
    }

    protected function relatedKeys(mixed $stream, int $limit): array
    {
        if (! is_string($stream) || ! Streams::exists($stream)) {
            return [];
        }

        $related = Streams::make($stream);
        $entries = $related->entries()->limit(100)->get();

        if ($entries->isEmpty()) {
            return [];
        }

        $keyName = $related->config('key_name', 'id');

        return $entries
            ->random(min($limit, $entries->count()))
            ->map(fn ($entry) => $entry->{$keyName})
            ->values()
            ->all();
    }

    protected function migrateStream(string $id, ?string $table = null): void
    {
        (new StreamMigrator)->migrate($this->stream($id), $table);
    }

    protected function migrateStreams(): void
    {
        (new StreamMigrator)->migrateDatabaseStreams();
    }

    protected function streamUrl(string $name, array $parameters = []): string
    {
        if (Route::has($name)) {
            return route($name, $parameters);
        }

        return URL::streams($name, $parameters);
    }

    protected function getStream(string $name, array $parameters = []): TestResponse
    {
        return $this->callStream('GET', $name, $parameters);
    }

    protected function postStream(string $name, array $parameters = []): TestResponse
    {
        return $this->callStream('POST', $name, $parameters);
    }

    protected function putStream(string $name, array $parameters = []): TestResponse
    {
        return $this->callStream('PUT', $name, $parameters);
    }

    protected function patchStream(string $name, array $parameters = []): TestResponse
    {
        return $this->callStream('PATCH', $name, $parameters);
    }

    protected function deleteStream(string $name, array $parameters = []): TestResponse
    {
        return $this->callStream('DELETE', $name, $parameters);
    }

    protected function callStream(string $method, string $name, array $parameters = []): TestResponse
    {
        return $this->call($method, $this->streamUrl($name, $parameters));
    }

    protected function defineStream(string $id, array $attributes = []): Stream
    {
        $attributes['id'] = $id;
        $attributes['handle'] = $attributes['handle'] ?? $id;
        $attributes['config']['source']['type'] = $attributes['config']['source']['type'] ?? 'file';

        return Streams::register($attributes);
    }

    protected function loadStream(string $file): Stream
    {
        return Streams::load($file);
    }

    protected function loadStreamsFrom(string $directory): void
    {
        Streams::loadDirectory($directory);
    }

    protected function restoreStreams(): void
    {
        RuntimeApplication::restoreStreams();
    }

    /**
     * Providers for a Streams package test.
     *
     * Includes Streams core, HTML, providers declared by the package under
     * test, and any extra providers the test needs (Livewire, for example).
     *
     * @param  array<int, class-string>  $providers
     * @return array<int, class-string>
     */
    protected function streamsPackageProviders(array $providers = []): array
    {
        $discovered = [
            HtmlServiceProvider::class,
            StreamsServiceProvider::class,
        ];

        $composer = RuntimeApplication::rootComposer();

        foreach ($composer['extra']['laravel']['providers'] ?? [] as $provider) {
            if (is_string($provider)) {
                $discovered[] = $provider;
            }
        }

        return array_values(array_unique(array_filter(
            array_merge($discovered, $providers),
            fn ($provider) => is_string($provider) && class_exists($provider)
        )));
    }

    protected function assertStreamExists(string $id): void
    {
        $this->assertTrue(
            Streams::exists($id),
            "Stream [{$id}] is not registered."
        );
    }

    protected function assertStreamMissing(string $id): void
    {
        $this->assertFalse(
            Streams::exists($id),
            "Stream [{$id}] was not expected to be registered."
        );
    }

    protected function assertEntryCount(string $stream, int $expected): void
    {
        $actual = $this->entries($stream)->count();

        $this->assertSame(
            $expected,
            $actual,
            "Stream [{$stream}] has {$actual} entries, expected {$expected}."
        );
    }

    protected function assertEntryExists(string $stream, mixed $key): void
    {
        $this->assertNotNull(
            Streams::repository($stream)->find($key),
            "Entry [{$key}] was not found in stream [{$stream}]."
        );
    }

    protected function assertEntryMissing(string $stream, mixed $key): void
    {
        $this->assertNull(
            Streams::repository($stream)->find($key),
            "Entry [{$key}] was found in stream [{$stream}]."
        );
    }
}
