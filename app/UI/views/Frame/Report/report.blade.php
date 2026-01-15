<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/report','Report'],['/report/frame','Frame'],['',$frame->name]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="page-content">
                @include("Frame.Report.reportPartial")
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
