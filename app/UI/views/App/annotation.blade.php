@php
    $annotation = [
        'annotationfe' => ['FE', '/annotation/fe', 'Corpus annotation for FE layer','ui::icon.frame'],
        'annotationfulltext' => ['Full text', '/annotation/fullText', 'Corpus annotation for all layers','ui::icon.frame'],
        'annotationset' => ['Annotation Sets by sentence', '/annotation/as', 'Check annotation sets by sentence.','ui::icon.frame'],
        'annotationsetlu' => ['Annotation Sets by LU', '/annotation/lu', 'Check annotation sets by LU.','ui::icon.frame'],
        'annotationsession' => ['Sessions', '/annotation/session', 'Sessions Report.','ui::icon.frame'],
        'annotationcxn' => ['Construction', '/annotation/cxn', 'Corpus annotation for constructions','ui::icon.construction','dev'],
        'annotationflex' => ['Flex-syntax', '/annotation/flex', 'Croft Flex-syntax annotation','ui::icon.frame','dev'],
        'annotationdynamic' => ['Dynamic mode', '/annotation/dynamicMode', 'Video annotation.', 'ui::icon.video' ],
        'annotationdeixis' => ['Deixis', '/annotation/deixis', 'Video annotation for deixis.', 'ui::icon.video'],
        'annotationcanvas' => ['Canvas', '/annotation/canvas', 'Video annotation for canvas.', 'ui::icon.video'],
        'annotationstaticbbox' => ['Static bbox', '/annotation/staticBBox', 'Image annotation.','ui::icon.image'],
        'annotationstaticevent' => ['Static event', '/annotation/staticEvent', 'Image annotation for eventive frames.','ui::icon.image'],
        'udparser' => ['UD parser', '/ud/parser', 'UD parsing using Trankit.','ui::icon.image'],
    ];
    $annotationType = [
        'corpus' => ['title' => "Corpus", "pages" => ['annotationfe','annotationfulltext','annotationcxn','annotationset','annotationsetlu','annotationsession','annotationflex']],
//        'video' => ['title' => "Video", "pages" => ['annotationdynamic','annotationdeixis','annotationcanvas']],
//        //'video' => ['title' => "Video", "pages" => ['annotationdeixis']],
//        'image' => ['title' => "Image", "pages" => ['annotationstaticbbox','annotationstaticevent']],
//        'parser' => ['title' => "Parser", "pages" => ['udparser']],
    ];

@endphp

<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['','Annotation']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Annotation
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    @foreach($annotationType as $type)
                        <div class="ui fluid card">
                            <div class="content bg-gray-200">
                                <div class="header">
                                    {{$type['title']}}
                                </div>
                            </div>
                            <div class="content">
                                <div class="card-grid dense">
                                    @foreach($type['pages'] as $category)
                                        @php
                                            $item = $annotation[$category];
                                            $mode = $item[4] ?? 'prod';
                                            if ((config('webtool.mode') == 'prod') && ($mode == 'dev')) {
                                                continue;
                                            }
                                        @endphp
                                        <a
                                            class="ui card option-card"
                                            data-category="{{$category}}"
                                            href="{{$item[1]}}"
                                            hx-boost="true"
                                        >
                                            <div class="content">
                                                <div class="header">
                                                    <x-dynamic-component :component="$item[3]" />
                                                    {{$item[0]}}
                                                </div>
                                                <div class="description">
                                                    {{$item[2]}}
                                                </div>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>

                            </div>

                        </div>
                    @endforeach
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
