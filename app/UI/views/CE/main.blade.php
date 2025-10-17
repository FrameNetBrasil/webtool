<x-dynamic-component component="layout.{{$_layout ?? 'detail'}}">
    <x-slot:title>
        FrameElement
    </x-slot:title>
    <x-slot:actions>
        <x-button label="List" color="primary" href="/frame"></x-button>
        <x-button label="New" color="secondary" href="/fe/new"></x-button>
    </x-slot:actions>
    @yield('content')
</x-dynamic-component>
