<div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-2">
    @foreach($records as $item)
        {{ ($this->runAction($item))(['item' => $item]) }}
    @endforeach
</div>
