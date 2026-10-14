<ul>
    @foreach ($stream->entries()->get() as $entry)
    <li>{{ $entry->title }}</li>
    @endforeach
</ul>
