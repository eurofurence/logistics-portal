<table class="min-w-full border border-gray-300">
    <thead>
        <tr>
            <th class="px-4 py-2 border border-gray-300">
                Status
            </th>
            <th class="px-4 py-2 border border-gray-300">
                Beschreibung
            </th>
        </tr>
    </thead>
    <tbody>
        @foreach (__('general.status_descriptions') as $state => $description)
            <tr>
                <td class="px-4 py-2 border border-gray-300">
                    {{ strtoupper(str_replace('_', ' ', $state)) }}
                </td>
                <td class="px-4 py-2 border border-gray-300">
                    {{ $description }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
