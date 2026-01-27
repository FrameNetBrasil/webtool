<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/frame','Frame'],['/frame/' . $frameElement->frame->idFrame,$frameElement->frame->name],['',$frameElement->frame->name.'.'.$frameElement?->name]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-header-main">
                            <div class="page-title-section">
                                <div class="page-title">
                                    <x-element.fe
                                        name="{{$frameElement->frame->name}}.{{$frameElement?->name}}"
                                        type="{{$frameElement->coreType}}"
                                        idColor="{{$frameElement->idColor}}"
                                    ></x-element.fe>
                                </div>
                                <div
                                    class="page-subtitle">{!! str_replace('ex>','code>',nl2br($frameElement->description)) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    <div class="frame-metadata-section">
{{--                        @include('Frame.Report.partials.frame-metadata')--}}
                    </div>

                    @include("FE.menu")
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
