<x-layout.index>
    <div class="ui warning message m-2">
        <div class="header">
            Warning
        </div>
        <p>
            {{$message}}
        </p>
        <x-link-button
            href="{{$goto}}"
            color="brown"
            label="{{$gotoLabel}}"
        >
        </x-link-button>
    </div>
</x-layout.index>
