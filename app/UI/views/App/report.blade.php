@php
    $reports = [
        'reportframe' => ['Frame', '/report/frame', 'List of all frames and its structure.','ui::icon.frame'],
        'reportlu' => ['LU', '/report/lu', 'List of lexical and visual Lexical Units','ui::icon.lu'],
//        'cxnreport' => ['Constructions', '/report/cxn', 'List of all constructions and its structure.', 'ui::icon.construction' ],
//        'reporttqr' => ['TQR', '/report/qualia', 'Structure of Ternary Qualia Relarion (TQR).', 'ui::icon.qualia'],
//        'reportst' => ['SemanticType', '/report/semanticType', 'List of Semantic Types and its hierarchy.','ui::icon.semantictype'],
//        'reportc5' => ['MoCCA', '/report/c5', 'List of all Comparative Concepts (CC) of MoCCA Project.','ui::icon.concept'],
    ];
@endphp

<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','Report']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Report
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    <div class="ui container">
                        <div class="card-grid dense">
                            @foreach($reports as $category => $report)
                                <a
                                    class="ui card option-card"
                                    data-category="{{$category}}"
                                    href="{{$report[1]}}"
                                    hx-boost="true"
                                >
                                    <div class="content">
                                        <div class="header">
                                            <x-dynamic-component :component="$report[3]"/>
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
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout::index>

