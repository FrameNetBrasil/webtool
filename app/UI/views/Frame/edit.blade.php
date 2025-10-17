@use("Carbon\Carbon")
<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/frame','Frame'],['',$frame?->name]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-header-main">
                            <div class="page-title-section">
                                <div class="page-title">
                                    <x-ui::element.frame name="{{$frame->name}}"></x-ui::element.frame>
                                </div>
                                <div
                                    class="page-subtitle">{!! str_replace('ex>','code>',nl2br($frame->description)) !!}</div>
                            </div>
                            @if(session('isAdmin'))
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
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
