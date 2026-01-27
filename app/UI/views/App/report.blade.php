@php
    $reports = [
        'reportframe' => ['Frame', '/report/frame', 'List of all frames and its structure.','ui::icon.frame'],
        'reportlu' => ['LU', '/report/lu', 'List of lexical and visual Lexical Units','ui::icon.lu'],
        'namespaceframe' => ['Frames by namespace', '/namespace', 'List of all frames grouped by namespaces.','ui::icon.namespace_raw'],
        'classes' => ['Class', '/report/class', 'List of all ontological classes.','ui::icon.frame'],
        'microframe' => ['Microframe', '/report/microframe', 'List of all microframes.','ui::icon.microframe'],
//        'cxnreport' => ['Constructions', '/report/cxn', 'List of all constructions and its structure.', 'ui::icon.construction' ],
//        'reporttqr' => ['TQR', '/report/qualia', 'Structure of Ternary Qualia Relarion (TQR).', 'ui::icon.qualia'],
//        'reportst' => ['SemanticType', '/report/semanticType', 'List of Semantic Types and its hierarchy.','ui::icon.semantictype'],
//        'reportc5' => ['MoCCA', '/report/c5', 'List of all Comparative Concepts (CC) of MoCCA Project.','ui::icon.concept'],
    ];
@endphp

<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['','Report']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Report
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    <div class="card-grid dense">
                        @foreach($reports as $category => $report)
                            @php
                                $mode = $report[4] ?? 'prod';
                                if ((config('webtool.mode') == 'prod') && ($mode == 'dev')) {
                                    continue;
                                }
                            @endphp
                            <a
                                class="ui card option-card"
                                data-category="{{$category}}"
                                href="{{$report[1]}}"
                                hx-boost="true"
                            >
                                <div class="content">
                                    <div class="header">
                                        <x-dynamic-component :component="$report[3]" />
                                        {{$report[0]}}
                                    </div>
                                    <div class="description">
                                        {{$report[2]}}
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>

