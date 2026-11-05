<?php

namespace Streams\Testing\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Streams\Core\Field\Field;
use Streams\Core\Stream\Stream;
use Streams\Core\Support\Facades\Streams;

class StreamMigrator
{
    public function migrate(Stream $stream, ?string $table = null): void
    {
        $table = $table ?: $stream->config('source.table', $stream->id);

        Schema::dropIfExists($table);

        $key = $stream->config('key_name', 'id');

        Schema::create($table, function (Blueprint $blueprint) use ($stream, $key) {
            foreach ($stream->fields as $field) {
                $this->addColumn($blueprint, $field, $key);
            }
        });
    }

    public function migrateDatabaseStreams(): void
    {
        foreach (Streams::collection() as $stream) {
            if ($stream->config('source.type') !== 'database') {
                continue;
            }

            $this->migrate($stream);
        }
    }

    protected function addColumn(Blueprint $blueprint, Field $field, string $key): void
    {
        $type = $this->columnType($field);
        $handle = $field->handle;

        $column = match ($type) {
            'integer' => $blueprint->integer($handle),
            'decimal' => $blueprint->decimal($handle),
            'boolean' => $blueprint->boolean($handle),
            'date' => $blueprint->date($handle),
            'datetime' => $blueprint->dateTime($handle),
            'json' => $blueprint->json($handle),
            default => $blueprint->string($handle),
        };

        if ($handle === $key) {
            if ($type === 'integer') {
                $column->primary();
            } else {
                $column->unique();
            }

            return;
        }

        if (! $this->required($field)) {
            $column->nullable();
        }
    }

    protected function columnType(Field $field): string
    {
        $type = $field->type === 'relationship'
            ? $this->relatedKeyType($field)
            : $field->type;

        return match ($type) {
            'integer' => 'integer',
            'decimal', 'number' => 'decimal',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
            'array', 'object', 'multiselect' => 'json',
            default => 'string',
        };
    }

    protected function relatedKeyType(Field $field): string
    {
        $related = $field->config('related');

        if (! is_string($related) || ! Streams::exists($related)) {
            return 'string';
        }

        $stream = Streams::make($related);
        $key = $stream->config('key_name', 'id');
        $keyField = $stream->fields->get($key);

        return $keyField?->type === 'integer' ? 'integer' : 'string';
    }

    protected function required(Field $field): bool
    {
        return (bool) $field->required || $field->hasRule('required');
    }
}
