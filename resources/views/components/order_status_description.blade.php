<ul>
    <ul>
        @foreach (__('general.status_descriptions') as $state => $description)
            <li><strong>{{ strtoupper(str_replace('_', ' ', $state)) }}:</strong> {{ $description }}</li>
            <br>
        @endforeach
    </ul>

</ul>
