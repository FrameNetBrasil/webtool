<x-layout.index>
    <div class="ui negative message m-2">
        <div class="header">
            Error
        </div>
        <p>
           {{$message}}
        </p>
        <x-link-button
            href="{{$goto}}"
            color="negative"
            label="{{$gotoLabel}}"
        >
        </x-link-button>
    </div>
</x-layout.index>
