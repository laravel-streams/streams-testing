<?php

namespace Streams\Testing;

use Streams\Core\Stream\Stream;
use Illuminate\Support\Facades\File;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function tearDown(): void
    {
        $this->restoreStreamsData();

        parent::tearDown();
    }

    public static function applicationBasePath()
    {
        return dirname(__DIR__).'/laravel';
    }

    protected function restoreStreamsData()
    {
        File::deleteDirectory(__DIR__.'/../laravel/streams');

        File::copyDirectory(
            __DIR__.'/../laravel/streams.bak',
            __DIR__.'/../laravel/streams'
        );

        Stream::resetMemory();
    }

    /** Remove active fixture data while retaining stream definitions and backups. */
    protected function clearData(): void
    {
        $directory = static::applicationBasePath().'/streams/data';

        File::deleteDirectory($directory);
        File::ensureDirectoryExists($directory);
        Stream::resetMemory();
    }

    /** Replace active data with the named committed fixtures (all source formats). */
    protected function loadFixtures(array $streams): void
    {
        $backup = static::applicationBasePath().'/streams.bak';

        foreach ($streams as $stream) {
            if (! is_string($stream) || ! preg_match('/^[a-zA-Z0-9_-]+$/D', $stream)
                || ! File::isFile($backup.'/'.$stream.'.json')) {
                throw new \InvalidArgumentException('Unknown or invalid fixture name.');
            }
        }

        $this->clearData();
        $directory = static::applicationBasePath().'/streams/data';

        foreach (array_unique($streams) as $stream) {
            foreach (File::glob($backup.'/data/'.$stream.'.*') as $file) {
                File::copy($file, $directory.'/'.basename($file));
            }

            if (File::isDirectory($backup.'/data/'.$stream)) {
                File::copyDirectory($backup.'/data/'.$stream, $directory.'/'.$stream);
            }
        }
    }
}
