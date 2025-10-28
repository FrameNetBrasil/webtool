<x-layout.index>
    <div class="ui info message m-2">
        <div class="header">
            Information
        </div>
        <p>
            {{$message}}
        </p>
        <x-link-button
            href="{{$goto}}"
            color="blue"
            label="{{$gotoLabel}}"
        >
        </x-link-button>
    </div>
</x-layout.index>
