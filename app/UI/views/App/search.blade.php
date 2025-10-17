<x-layout.browser>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['','Home']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div
            id="gridArea"
            class="h-full"
        >
            <div class="grid h-full">
                <div id="frameTableContainer" class="col-6">
                    @include("App.treeFrame")
                </div>
                <div id="feluTableContainer" class="col-6">
                    @include("App.treeLU")
                </div>
            </div>
        </div>
    </x-slot:main>
</x-layout.browser>
