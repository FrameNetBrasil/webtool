@php
    $language = session('currentLanguage')->language;
@endphp
<x-layout.page>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['','Reinventa']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div
            class="ui card h-full w-full overflow-y-auto p-2"
        >
            <div class="dashboard-title">{{ __("dashboard.subcorpus") }}: Frame2</div>
            <div
                class="dashboard-corpus"
                hx-trigger="load"
                hx-target="this"
                hx-get="/dashboard/frame2"
            >
                <x-loader></x-loader>
            </div>

            <div class="dashboard-title">{{ __("dashboard.subcorpus") }}: Pedro pelo Mundo</div>
            <div
                class="dashboard-corpus"
                hx-trigger="load"
                hx-target="this"
                hx-get="/dashboard/frame2PPM"
            >
                <x-loader></x-loader>
            </div>

            <div class="dashboard-title">{{ __("dashboard.subcorpus") }}: NLG</div>
            <div
                class="dashboard-corpus"
                hx-trigger="load"
                hx-target="this"
                hx-get="/dashboard/frame2NLG"
            >
                <x-loader></x-loader>
            </div>

            <div class="dashboard-title">{{ __("dashboard.subcorpus") }}: Gesture</div>
            <div
                class="dashboard-corpus"
                hx-trigger="load"
                hx-target="this"
                hx-get="/dashboard/frame2Gesture"
            >
                <x-loader></x-loader>
            </div>

            <div class="dashboard-title">{{ __("dashboard.subcorpus") }}: Audition</div>
            <div
                class="dashboard-corpus"
                hx-trigger="load"
                hx-target="this"
                hx-get="/dashboard/audition"
            >
                <x-loader></x-loader>
            </div>


            <div class="dashboard-title">{{ __("dashboard.subcorpus") }}: Framed Multi30k</div>
            <div
                hx-trigger="load"
                hx-target="this"
                hx-get="/dashboard/multi30k"
            >
                <x-loader></x-loader>
            </div>


        </div>
    </x-slot:main>
</x-layout.page>
