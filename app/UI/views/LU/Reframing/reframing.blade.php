<x-layout.edit>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['/structure','Structure'],['/reframing','Reframing'],['',$lu->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div class="page-content h-full">
            <div class="content-container h-full">
                <div id="luReframing" class="flex flex-column h-full">
                    <div class="flex flex-row align-content-start">
                        <div class="col-12 sm:col-12 md:col-12 lg:col-7 xl:col-6">
                            <h1>
                                <x-element::lu frame="{{$lu->frameName}}" name="{{$lu->name}}"></x-element::lu>
                            </h1>
                        </div>
                        <div
                            class="col-12 sm:col-12 md:col-12 lg:col-5 xl:col-6 flex gap-1 flex-wrap align-items-center justify-content-end">
                            <div class="ui label wt-tag-en">
                                {{$language->language}}
                            </div>
                            <div class="ui label wt-tag-id">
                                #{{$lu->idLU}}
                            </div>
                            <x-link-button color="secondary" href="/report/frame/{{$lu->idFrame}}"
                                           label="{{$lu->frameName}}"></x-link-button>
                        </div>
                    </div>
                    <div class="flex-grow-1 h-full flex flex-column">
                        @include("LU.Reframing.formReframing")
                        <div id="reframingEdit">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot:main>
</x-layout.edit>
