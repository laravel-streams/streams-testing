<?php

namespace Streams\Testing;

use Composer\Autoload\ClassLoader;
use RuntimeException;

/**
 * Builds a per-process Laravel application for Streams package tests.
 *
 * Streams reads stream files, composer.json, and composer.lock from the
 * application base path. This runtime keeps that contract without writing
 * into the package checkout or the host vendor directory.
 */
class RuntimeApplication
{
    protected static ?string $path = null;

    public static function path(): string
    {
        self::ensureCreated();

        return self::$path;
    }

    public static function boot(): void
    {
        self::ensureCreated();
        self::restoreStreams();
    }

    public static function restoreStreams(): void
    {
        $destination = self::path().'/streams';

        self::deleteDirectory($destination);
        self::copyTree(self::fixturePath(), $destination);
    }

    public static function vendorPath(): string
    {
        $loader = (new \ReflectionClass(ClassLoader::class))->getFileName();

        if (! is_string($loader)) {
            throw new RuntimeException('Unable to locate the Composer vendor directory.');
        }

        return dirname($loader, 2);
    }

    public static function projectRoot(): string
    {
        return dirname(self::vendorPath());
    }

    /**
     * @return array<string, mixed>
     */
    public static function rootComposer(): array
    {
        $file = self::projectRoot().'/composer.json';

        if (! is_file($file)) {
            return [];
        }

        $composer = json_decode((string) file_get_contents($file), true);

        return is_array($composer) ? $composer : [];
    }

    public static function packageRoot(): string
    {
        return dirname(__DIR__);
    }

    protected static function ensureCreated(): void
    {
        $path = self::runtimePath();
        $signature = self::signature();
        $marker = $path.'/.streams-testing';

        if (is_file($marker) && trim((string) file_get_contents($marker)) === $signature && is_link($path.'/vendor')) {
            self::$path = $path;

            return;
        }

        self::prune();
        self::deleteDirectory($path);
        self::copySkeleton($path);
        self::linkVendor($path);
        self::writeComposerMetadata($path);
        self::ensureWritableDirectories($path);
        self::copyTree(self::fixturePath(), $path.'/streams');

        file_put_contents($marker, $signature);
        self::$path = $path;
    }

    protected static function runtimePath(): string
    {
        $token = getenv('TEST_TOKEN');
        $token = ($token === false || $token === '') ? 'run' : $token;

        return sys_get_temp_dir().'/streams-testing/'.$token.'-'.getmypid();
    }

    protected static function fixturePath(): string
    {
        $fixtures = self::packageRoot().'/fixtures/streams';

        if (is_dir($fixtures)) {
            return $fixtures;
        }

        throw new RuntimeException('Streams testing fixtures are missing.');
    }

    protected static function signature(): string
    {
        $root = self::packageRoot();

        return md5(implode('|', [
            (string) filemtime(__FILE__),
            (string) filemtime($root.'/composer.json'),
            (string) filemtime($root.'/bootstrap/app.php'),
            (string) filemtime($root.'/config/app.php'),
            (string) filemtime(self::fixturePath()),
        ]));
    }

    protected static function copySkeleton(string $path): void
    {
        if (! function_exists('Orchestra\Testbench\default_skeleton_path')) {
            throw new RuntimeException('Orchestra Testbench is required to build the testing application.');
        }

        $skeleton = \Orchestra\Testbench\default_skeleton_path();

        if (! is_dir($skeleton)) {
            throw new RuntimeException("Testbench skeleton [{$skeleton}] is missing.");
        }

        self::copyTree($skeleton, $path, ['vendor']);
        self::overlay($path);
    }

    protected static function overlay(string $path): void
    {
        $root = self::packageRoot();

        self::copyTree($root.'/resources/views', $path.'/resources/views');
        self::copyTree($root.'/public/vendor/testing', $path.'/public/vendor/testing');
        self::copyTree($root.'/storage/app', $path.'/storage/app');

        copy($root.'/config/app.php', $path.'/config/app.php');
        copy($root.'/bootstrap/app.php', $path.'/bootstrap/app.php');
    }

    protected static function linkVendor(string $path): void
    {
        $link = $path.'/vendor';
        $vendor = self::vendorPath();

        if (! is_file($vendor.'/autoload.php')) {
            throw new RuntimeException("Composer autoload was not found at [{$vendor}/autoload.php].");
        }

        if (is_link($link) || is_file($link)) {
            unlink($link);
        } elseif (is_dir($link)) {
            self::deleteDirectory($link);
        }

        if (! symlink($vendor, $link)) {
            throw new RuntimeException("Unable to link [{$vendor}] to [{$link}].");
        }
    }

    protected static function writeComposerMetadata(string $path): void
    {
        $composer = [
            'name' => 'streams/testing-runtime',
            'type' => 'project',
            'description' => 'Isolated Streams testing application.',
            'require' => new \stdClass,
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                ],
            ],
        ];

        file_put_contents(
            $path.'/composer.json',
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );

        file_put_contents(
            $path.'/composer.lock',
            json_encode([
                'packages' => [],
                'packages-dev' => self::addonPackages(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
        );
    }

    /**
     * @return array<int, array{name: string, version: string, type: string}>
     */
    protected static function addonPackages(): array
    {
        $packages = [];
        $installed = self::vendorPath().'/composer/installed.json';

        if (is_file($installed)) {
            $contents = json_decode((string) file_get_contents($installed), true);
            $list = is_array($contents) ? ($contents['packages'] ?? $contents) : [];

            foreach ($list as $package) {
                if (! is_array($package) || ($package['type'] ?? null) !== 'streams-addon') {
                    continue;
                }

                if (! isset($package['name'])) {
                    continue;
                }

                $packages[$package['name']] = [
                    'name' => $package['name'],
                    'version' => (string) ($package['version'] ?? 'dev-local'),
                    'type' => 'streams-addon',
                ];
            }
        }

        $root = self::rootComposer();

        if (($root['type'] ?? null) === 'streams-addon' && isset($root['name']) && ! isset($packages[$root['name']])) {
            $packages[$root['name']] = [
                'name' => $root['name'],
                'version' => 'dev-local',
                'type' => 'streams-addon',
            ];
        }

        ksort($packages);

        return array_values($packages);
    }

    protected static function ensureWritableDirectories(string $path): void
    {
        foreach ([
            'storage/app/public',
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
            'bootstrap/cache',
        ] as $directory) {
            $target = $path.'/'.$directory;

            if (! is_dir($target) && ! mkdir($target, 0777, true) && ! is_dir($target)) {
                throw new RuntimeException("Unable to create [{$target}].");
            }
        }

        foreach (glob($path.'/bootstrap/cache/*.php') ?: [] as $cache) {
            unlink($cache);
        }
    }

    /**
     * @param  array<int, string>  $skip
     */
    protected static function copyTree(string $from, string $to, array $skip = []): void
    {
        $from = rtrim($from, DIRECTORY_SEPARATOR);

        if (! is_dir($to) && ! mkdir($to, 0777, true) && ! is_dir($to)) {
            throw new RuntimeException("Unable to create [{$to}].");
        }

        $filter = new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            function (\SplFileInfo $current) use ($from, $skip) {
                $relative = substr($current->getPathname(), strlen($from) + 1);
                $top = explode(DIRECTORY_SEPARATOR, $relative)[0];

                return ! in_array($top, $skip, true);
            }
        );

        $iterator = new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item) {
            $relative = substr($item->getPathname(), strlen($from) + 1);
            $normalized = str_replace('\\', '/', $relative);

            if (str_starts_with($normalized, 'bootstrap/cache/') && str_ends_with($normalized, '.php')) {
                continue;
            }

            if (str_starts_with($normalized, 'storage/framework/views/') && str_ends_with($normalized, '.php')) {
                continue;
            }

            $target = $to.DIRECTORY_SEPARATOR.$relative;

            if ($item->isDir()) {
                if (! is_dir($target) && ! mkdir($target, 0777, true) && ! is_dir($target)) {
                    throw new RuntimeException("Unable to create [{$target}].");
                }

                continue;
            }

            $directory = dirname($target);

            if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
                throw new RuntimeException("Unable to create [{$directory}].");
            }

            if (! copy($item->getPathname(), $target)) {
                throw new RuntimeException("Unable to copy [{$item->getPathname()}] to [{$target}].");
            }
        }
    }

    protected static function deleteDirectory(string $directory): void
    {
        if (is_link($directory) || is_file($directory)) {
            unlink($directory);

            return;
        }

        if (! is_dir($directory)) {
            return;
        }

        $items = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            $pathname = $item->getPathname();

            if ($item->isLink() || ! $item->isDir()) {
                unlink($pathname);

                continue;
            }

            self::deleteDirectory($pathname);
        }

        rmdir($directory);
    }

    protected static function prune(): void
    {
        $root = sys_get_temp_dir().'/streams-testing';

        if (! is_dir($root)) {
            return;
        }

        $cutoff = time() - 86400;

        foreach (glob($root.'/*', GLOB_ONLYDIR) ?: [] as $directory) {
            if (filemtime($directory) < $cutoff) {
                self::deleteDirectory($directory);
            }
        }
    }
}
