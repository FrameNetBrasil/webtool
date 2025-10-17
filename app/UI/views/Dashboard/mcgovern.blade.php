@php
$language = session('currentLanguage')->language;
@endphp
<x-wt::indexLayout :data="$data">
    <div class="w-full">
        <div class="dashboard-title">Frames</div>
        @include('dashboard.mcgovernFrame')

        <div class="dashboard-title">Qualia</div>
        @include('dashboard.mcgovernQualia')

        <div class="dashboard-title">Annotation</div>
        @include('dashboard.mcgovernAnnotation')

    </div>
</x-wt::indexLayout>