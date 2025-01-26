@if (!empty($notes))
    <ul>
        @foreach (array_slice($notes, 0, 3) as $note)
            <li>- {!! $note !!}</li>
        @endforeach
    </ul>
@else
    <p>@lang('general.no_entries')</p>
@endif
