<x-layout.index>
    <div class="ui warning message m-2">
        <div class="header">
            Warning
        </div>
        <p>
            {{$message}}
        </p>
        <x-button
            href="{{$goto}}"
            color="yellow"
            label="{{$gotoLabel}}"
        >
        </x-button>
    </div>
</x-layout.index>
