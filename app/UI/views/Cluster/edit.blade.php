@use("Carbon\Carbon")
<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/cluster','Cluster'],['',$frame?->name]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-header-main">
                            <div class="page-title-section">
                                <div class="page-title">
                                    <x-ui::element.cluster :name="$frame->name"></x-ui::element.cluster>
                                </div>
                                <div
                                    class="page-subtitle">{!! str_replace('ex>','code>',nl2br($frame->description)) !!}</div>
                            </div>
                            @if(session('isAdmin'))
                                <button
                                    class="ui right labeled icon button"
                                    hx-get="/cluster/nextFrom/{{$frame->idFrame}}"
                                >
                                    <i class="right arrow icon"></i>
                                    Next
                                </button>
                                <button
                                    x-data
                                    type=button"
                                    class="ui danger button"
                                    @click="messenger.confirmDelete(`Removing Cluster '{{$frame?->name}}'.`, '/cluster/{{$frame->idFrame}}')"
                                >Delete</button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    <div class="frame-metadata-section">
                        @include('Cluster.Report.partials.frame-metadata')
                    </div>

                    @include("Cluster.menu")
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
