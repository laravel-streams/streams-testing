# Streams Testing

A comprehensive testing package for the Streams platform that provides a pre-configured Laravel testing environment with sample data and utilities for testing Streams applications.

## Features

- **Pre-configured Test Environment**: Extends Orchestra Testbench for seamless Laravel/Streams integration
- **Sample Test Data**: Includes Star Wars-themed sample streams (films, people, planets, species, starships, vehicles, files)
- **Automatic Data Restoration**: Automatically restores test data after each test to ensure clean state
- **PHPUnit 10 Support**: Fully compatible with the latest PHPUnit version
- **Custom TestCase**: Base test case class with built-in utilities for Streams testing

## Installation

Install the package via Composer:

```bash
composer require streams/testing --dev
```

## Usage

### Basic Test Setup

Extend the `Streams\Testing\TestCase` class in your tests:

```php
<?php

namespace YourApp\Tests;

use Streams\Core\Stream\Stream;
use Streams\Core\Support\Facades\Streams;

class YourTest extends \Streams\Testing\TestCase
{
    public function test_example()
    {
        // Access pre-configured test streams
        $films = Streams::entries('films');
        
        $this->assertGreaterThan(0, $films->count());
    }
}
```

### Available Test Data

The package includes the following pre-configured streams with sample data:

- **films** - 7 Star Wars films
- **people** - Character data
- **planets** - Planet information
- **species** - Species data
- **starships** - Starship details
- **vehicles** - Vehicle information
- **files** - File handling examples

### TestCase Features

The base `TestCase` class provides:

- **Automatic Teardown**: Restores streams data after each test
- **Laravel Integration**: Full Laravel application context via Orchestra Testbench
- **Application Base Path**: Pre-configured Laravel application structure in `laravel/` directory

### Example Tests

```php
public function test_loads_testing_streams()
{
    $stream = Streams::make('films');
    
    $this->assertInstanceOf(Stream::class, $stream);
}

public function test_queries_stream_entries()
{
    $entries = Streams::entries('films')
        ->where('director', 'George Lucas')
        ->get();
    
    $this->assertNotEmpty($entries);
}

public function test_creates_new_entry()
{
    $entry = Streams::make('films')->create([
        'title' => 'A New Film',
        'director' => 'Test Director',
    ]);
    
    $this->assertEquals('A New Film', $entry->title);
}
```

## Configuration

The package includes a `phpunit.xml` configuration file. You can customize it for your needs:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Your Test Suite">
            <directory suffix="Test.php">./tests</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

## Running Tests

Run your tests using PHPUnit:

```bash
# Run all tests
vendor/bin/phpunit

# Run with detailed output
vendor/bin/phpunit --testdox

# Run specific test file
vendor/bin/phpunit tests/YourTest.php
```

## How It Works

1. **Test Environment**: The package uses Orchestra Testbench to create a full Laravel application context for testing
2. **Data Management**: Sample streams data is stored in `laravel/streams/` and backed up in `laravel/streams.bak/`
3. **Automatic Cleanup**: After each test, the `tearDown()` method automatically restores the original test data from the backup
4. **Laravel Application**: A complete Laravel application structure is provided in the `laravel/` directory with all necessary configuration files

## Requirements

- PHP 8.1 or higher
- Laravel 10.x or 11.x
- Streams Core ^2.0

## Documentation

For more information about the Streams platform, visit [streams.dev](https://streams.dev/docs).

## License

The Streams Testing package is open-sourced software licensed under the [MIT license](LICENSE.md)
