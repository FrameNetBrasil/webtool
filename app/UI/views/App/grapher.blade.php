@php
    $options = [
        'frame' => ['Frame', '/grapher/frame', '','ui::icon.frame'],
        'domain' => ['Domain', '/grapher/domain', '','ui::icon.domain'],
        'scenario' => ['Scenario', '/grapher/scenario', '','ui::icon.frame'],
        'daisy' => ['Daisy', '/daisy', '','ui::icon.frame'],
    ];
@endphp

<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['','Grapher']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Grapher
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    <div class="card-grid dense">
                        @foreach($options as $category => $option)
                            @php
                                $mode = $option[4] ?? 'prod';
                                if ((config('webtool.mode') == 'prod') && ($mode == 'dev')) {
                                    continue;
                                }
                            @endphp
                            <a
                                class="ui card option-card"
                                data-category="{{$category}}"
                                href="{{$option[1]}}"
                                hx-boost="true"
                            >
                                <div class="content">
                                    <div class="header">
                                        <x-dynamic-component :component="$option[3]" />
                                        {{$option[0]}}
                                    </div>
                                    <div class="description">
                                        {{$option[2]}}
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </main>
    </div>
</x-layout::index>
