<ul>
    @foreach ($stream->entries()->get() as $entry)
    <li>{{ $entry->name }}</li>
    @endforeach
</ul>
