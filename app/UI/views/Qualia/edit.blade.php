<x-layout.index>
    <x-layout.edit>
        <x-slot:title>
            @include('Structure.Frame.title')
        </x-slot:title>
        <x-slot:menu>
            @include('Structure.Frame.menu')
        </x-slot:menu>
        <x-slot:pane>
            <div id="framesPane">
            </div>
        </x-slot:pane>
        <x-slot:footer></x-slot:footer>
    </x-layout.edit>
</x-layout.index>