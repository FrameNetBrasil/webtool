<x-layout.resource>
    <x-slot:title>
        Domain
    </x-slot:title>
    <x-slot:actions>
        <x-button
            label="New Domain"
            color="secondary"
            hx-get="/domain/new"
            hx-target="#editArea"
            hx-swap="innerHTML"
        ></x-button>
    </x-slot:actions>
    <x-slot:grid>
        <div
            hx-trigger="load"
            hx-target="this"
            hx-swap="outerHTML"
            hx-get="/domain/grid"
        ></div>
    </x-slot:grid>
    <x-slot:edit>
        <div id="editArea">

        </div>
    </x-slot:edit>
</x-layout.resource>
