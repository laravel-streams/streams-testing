<?php

namespace Streams\Testing\Tests;

use Streams\Core\Support\Facades\Streams;

class FixtureHelpersTest extends \Streams\Testing\TestCase
{
    public function test_clear_data_removes_entries_and_preserves_definitions_and_backups()
    {
        $this->assertSame(7, Streams::entries('films')->count());
        $this->clearData();

        $this->assertSame(0, Streams::entries('films')->count());
        $this->assertFileExists(base_path('streams/films.json'));
        $this->assertFileExists(base_path('streams.bak/data/films.json'));

        $this->restoreStreamsData();
        $this->assertSame(7, Streams::entries('films')->count());
    }

    public function test_load_fixtures_replaces_data_with_only_requested_streams()
    {
        $this->loadFixtures(['films']);

        $this->assertSame(7, Streams::entries('films')->count());
        $this->assertSame(0, Streams::entries('people')->count());
        $this->assertDirectoryExists(base_path('streams/data/films/json'));
        $this->assertFileDoesNotExist(base_path('streams/data/people.json'));

        $this->loadFixtures([]);
        $this->assertSame(0, Streams::entries('films')->count());
    }

    public function test_invalid_fixture_names_leave_existing_data_untouched()
    {
        try {
            $this->loadFixtures(['films', '../config/app']);
            $this->fail('Invalid fixture names must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertSame(7, Streams::entries('films')->count());
            $this->assertFileExists(base_path('streams/data/people.json'));
        }
    }
}
