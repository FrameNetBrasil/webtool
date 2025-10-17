<x-layout.index>
    <x-layout.browser>
        <x-slot:title>
            @include('Structure.Qualia.title')
        </x-slot:title>
        <x-slot:search>
            @include('Structure.Qualia.search')
        </x-slot:search>
        <x-slot:grid>
            <div id="qualiaGrid" class="h-full p-0 w-full">
                @include('Structure.Qualia.grid')
            </div>
        </x-slot:grid>
        <x-slot:footer></x-slot:footer>
    </x-layout.browser>
</x-layout.index>
