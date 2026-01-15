@use("Carbon\Carbon")
<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/frame','Frame'],['',$frame?->name]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-header-main">
                            <div class="page-title-section">
                                <div class="page-title">
                                    <x-ui::element.frame_ns :frame="$frame"></x-ui::element.frame_ns>
                                </div>
                                <div
                                    class="page-subtitle">{!! str_replace('ex>','code>',nl2br($frame->description)) !!}</div>
                            </div>
                            @if(session('isAdmin'))
                                <button
                                    class="ui right labeled icon button"
                                    hx-get="/frame/clone/{{$frame->idFrame}}"
                                >
                                    <i class="clone icon"></i>
                                    Clone
                                </button>
                            @endif
                            @if(session('isAdmin'))
                                <button
                                    class="ui right labeled icon button"
                                    hx-get="/frame/nextFrom/{{$frame->idFrame}}"
                                >
                                    <i class="right arrow icon"></i>
                                    Next
                                </button>
                                <button
                                    x-data
                                    type=button"
                                    class="ui danger button"
                                    @click="messenger.confirmDelete(`Removing Frame '{{$frame?->name}}'.`, '/frame/{{$frame->idFrame}}')"
                                >Delete</button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    <div class="frame-metadata-section">
                        @include('Frame.Report.partials.frame-metadata')
                    </div>

                    @include("Frame.menu")
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
