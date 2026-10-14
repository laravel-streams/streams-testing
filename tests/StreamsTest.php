<?php

namespace Streams\Testing\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Streams\Core\StreamsServiceProvider;
use Streams\Core\Support\Facades\Addons;
use Streams\Core\Support\Facades\Streams;
use Streams\Testing\TestCase;
use Streams\Testing\TestServiceProvider;

class StreamsTest extends TestCase
{
    public function test_sample_films_are_available()
    {
        $this->assertStreamExists('films');
        $this->assertEntryCount('films', 7);
        $this->assertSame('A New Hope', $this->entry('films', 4)->title);
    }

    public function test_created_entries_do_not_leak_into_the_next_test()
    {
        $this->createEntry('films', [
            'episode_id' => 99,
            'title' => 'Probe',
        ]);

        $this->assertEntryCount('films', 8);
        $this->assertEntryExists('films', 99);
    }

    public function test_fake_entries_honor_overrides()
    {
        $people = Streams::entries('people')->get()->map(fn ($person) => $person->person_id)->all();

        $film = $this->fakeEntry('films', [
            'episode_id' => 98,
            'title' => 'Faked',
        ]);

        $this->assertSame('Faked', $film->title);
        $this->assertNotEmpty($film->director);
        $this->assertNotEmpty(array_intersect((array) $film->characters, $people));
        $this->assertEntryCount('films', 8);
    }

    public function test_migrate_stream_accepts_a_generated_row()
    {
        $this->migrateStream('films');

        $this->assertTrue(Schema::hasTable('films'));

        DB::table('films')->insert($this->row('films', $this->fakeAttributes('films', [
            'episode_id' => 50,
            'title' => 'Schema',
        ])));

        $this->assertSame('Schema', DB::table('films')->where('episode_id', 50)->value('title'));
    }

    public function test_migrate_streams_skips_file_sources()
    {
        $this->defineStream('widgets', [
            'config' => [
                'source' => [
                    'type' => 'database',
                    'table' => 'widgets',
                ],
                'key_name' => 'id',
            ],
            'fields' => [
                [
                    'handle' => 'id',
                    'type' => 'integer',
                    'required' => true,
                ],
                [
                    'handle' => 'name',
                    'type' => 'string',
                ],
            ],
        ]);

        $this->migrateStreams();

        $this->assertTrue(Schema::hasTable('widgets'));
        $this->assertFalse(Schema::hasTable('films'));
    }

    public function test_stream_requests_follow_core_routes()
    {
        Route::streams('films/{id}', [
            'stream' => 'films',
            'view' => 'film',
        ]);

        $this->getStream('films/{id}', ['id' => 4])->assertSee('A New Hope');

        Route::get('catalog/{id}', [
            'as' => 'films.catalog',
            'uses' => fn () => 'named',
        ]);

        $this->assertStringEndsWith('/catalog/4', $this->streamUrl('films.catalog', ['id' => 4]));
    }

    public function test_streams_can_be_defined_for_a_single_test()
    {
        $this->assertStreamMissing('probes');

        $this->defineStream('probes', [
            'fields' => [
                [
                    'handle' => 'name',
                    'type' => 'string',
                ],
            ],
        ]);

        $probe = $this->createEntry('probes', [
            'id' => 'alpha',
            'name' => 'Alpha',
        ]);

        $this->assertSame('Alpha', $probe->name);
        $this->assertEntryCount('probes', 1);
    }

    public function test_database_defaults_to_memory_sqlite()
    {
        $this->assertSame('testing', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.testing.database'));

        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        $this->assertTrue(Schema::hasTable('widgets'));
    }

    public function test_application_is_isolated_from_package_files()
    {
        $this->assertStringStartsWith(sys_get_temp_dir(), base_path());
        $this->assertFileExists(base_path('streams/films.json'));
        $this->assertFileDoesNotExist(base_path('streams.bak'));
        $this->assertTrue(is_link(base_path('vendor')));
    }

    public function test_package_under_test_is_registered()
    {
        $providers = $this->streamsPackageProviders();

        $this->assertContains(StreamsServiceProvider::class, $providers);
        $this->assertContains(TestServiceProvider::class, $providers);
        $this->assertTrue(Addons::collection()->has('streams/testing'));
        $this->assertTrue(Streams::exists('films'));
    }

    protected function row(string $stream, array $attributes): array
    {
        $row = [];

        foreach ($this->stream($stream)->fields as $field) {
            if (! array_key_exists($field->handle, $attributes)) {
                continue;
            }

            $value = $attributes[$field->handle];

            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }

            if (is_array($value)) {
                $value = json_encode($value);
            }

            $row[$field->handle] = $value;
        }

        return $row;
    }
}
