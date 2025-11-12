# Streams Testing

A comprehensive testing package for the Streams platform that provides a pre-configured Laravel testing environment with sample data and utilities for testing Streams applications.

## Features

- **Pre-configured Test Environment**: Extends Orchestra Testbench for seamless Laravel/Streams integration
- **Sample Test Data**: Includes Star Wars-themed sample streams (films, people, planets, species, starships, vehicles, files)
- **Automatic Data Restoration**: Automatically restores test data after each test to ensure clean state
- **PHPUnit 10 Support**: Fully compatible with the latest PHPUnit version
- **Custom TestCase**: Base test case class with built-in utilities for Streams testing

## Quick Start

### Installation

```bash
composer require streams/testing --dev
```

### Create phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Tests">
            <directory suffix="Test.php">./tests</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_KEY" value="base64:aiGINJ0oFnqrMGUwJYWJuhe6meZoW+GqppwDJD4YZeM="/>
    </php>
</phpunit>
```

### Write Your First Test

```php
<?php

namespace Tests;

use Streams\Core\Support\Facades\Streams;

class FilmTest extends \Streams\Testing\TestCase
{
    public function test_can_query_films()
    {
        $films = Streams::entries('films')->get();
        
        $this->assertCount(7, $films);
    }
    
    public function test_can_create_film()
    {
        $film = Streams::make('films')->create([
            'title' => 'New Film',
            'director' => 'New Director',
        ]);
        
        $this->assertEquals('New Film', $film->title);
    }
}
```

### Run Tests

```bash
vendor/bin/phpunit
```

## What's Included

### Test Data

Pre-configured sample streams with Star Wars data:

- **films** - 7 films with directors, release dates, and more
- **people** - Characters with attributes like homeworld, species
- **planets** - Planetary data including climate, terrain, population
- **species** - Species classifications and characteristics
- **starships** - Starship specifications and capabilities
- **vehicles** - Vehicle details and stats
- **files** - File handling examples

### TestCase Features

The base `TestCase` provides:

- Automatic test data restoration after each test
- Full Laravel application context
- Orchestra Testbench integration
- Pre-configured streams environment

### Example Usage

```php
// Query entries
$films = Streams::entries('films')
    ->where('director', 'George Lucas')
    ->orderBy('release_date')
    ->get();

// Create entries
$film = Streams::make('films')->create([
    'title' => 'A New Film',
    'director' => 'New Director',
]);

// Update entries
$film->update(['director' => 'Updated Director']);

// Delete entries
$film->delete();

// Count entries
$count = Streams::entries('films')->count();
```

## Documentation

Comprehensive documentation is available at `/docs/testing/introduction` (see `docs/00-introduction.md` in this repository):

- **[Introduction](docs/00-introduction.md)** - Overview and key concepts (`/docs/testing/introduction`)
- **[Installation](docs/01-installation.md)** - Setup and configuration
- **[Test Data](docs/02-test-data.md)** - Understanding sample streams
- **[Writing Tests](docs/03-writing-tests.md)** - Test patterns and best practices
- **[Configuration](docs/04-configuration.md)** - Advanced configuration options
- **[Troubleshooting](docs/05-troubleshooting.md)** - Common issues and solutions

### Quick Links

- 📚 [Full Streams Documentation](https://streams.dev/docs)
- 🚀 [Getting Started Guide](docs/01-installation.md)
- 💡 [Test Examples](docs/03-writing-tests.md)
- 🔧 [Configuration Guide](docs/04-configuration.md)
- ❓ [Troubleshooting](docs/05-troubleshooting.md)

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x
- Streams Core ^2.0
- PHPUnit 10.x

## How It Works

1. **Test Environment**: Uses Orchestra Testbench to create a full Laravel application context
2. **Data Management**: Sample data stored in `laravel/streams/` with backups in `laravel/streams.bak/`
3. **Automatic Cleanup**: The `tearDown()` method restores original test data after each test
4. **Laravel Integration**: Complete Laravel application structure in `laravel/` directory

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The Streams Testing package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Support

- **Documentation**: https://streams.dev/docs/testing/introduction
- **Issues**: https://github.com/laravel-streams/streams-testing/issues
- **Discord**: Join the Streams community
