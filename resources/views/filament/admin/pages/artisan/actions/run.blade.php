<button
    wire:click="{{ $action->getLivewireClickHandler() }}"
    wire:target="{{ $action->getLivewireTarget() }}"
    x-on:click="{{ $action->getAlpineClickHandler() }}"
    class="flex flex-col items-start justify-start w-full h-full p-4 transition duration-200 ease-in-out bg-white border rounded-lg cursor-pointer dark:bg-gray-900 dark:border-gray-700"
>
    @if(!$item->error)
        <div class="flex justify-start gap-2 mb-2 text-xl font-bold transition duration-200 ease-in-out">
            <div class="flex flex-col justify-center item-center">
                <x-icon name="heroicon-s-command-line" class="w-4 h-4" />
            </div>
            <div>
                <code>{{ $item->name }}</code>
            </div>
        </div>

        <div class="text-gray-500 dark:text-gray-200">
            {{ $item->description }}
        </div>

        <div class="flex justify-start gap-2 my-4">
            @if($item->arguments != 'null')
                @foreach(json_decode($item->arguments) as $arg)
                    <x-filament::badge color="warning" tooltip="Arguments" icon="heroicon-s-document-text">
                        {{ str($arg->name)->replace('_', ' ')->replace('-', ' ')->title() }}
                    </x-filament::badge>
                @endforeach
            @endif

            @if($item->options != 'null')
                @foreach(json_decode($item->options) as $op)
                    <x-filament::badge color="info" tooltip="Option" icon="heroicon-s-bars-3-center-left">
                        {{ str($op->name)->replace('_', ' ')->replace('-', ' ')->title() }}
                    </x-filament::badge>
                @endforeach
            @endif
        </div>
    @else
        <div class="flex justify-start gap-2 mb-4 ">
            <div class="flex flex-col justify-center item-center">
                <x-icon name="heroicon-s-command-line" class="w-4 h-4" />
            </div>
            <div>
                {{ $item->name }}
            </div>
        </div>

        <div class="text-gray-500 dark:text-gray-200">{{ $item->error }}</div>
    @endif
</button>
