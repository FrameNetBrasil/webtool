@php
    $options = [
        'frame' => ['Frame', '/frame', '','ui::icon.frame'],
//        'lexicon' => ['Lexicon', '/lexicon3', '','ui::icon.domain'],
        'lemma' => ['Lemmas', '/lemma', '','ui::icon.domain'],
        'form' => ['Forms', '/form', '','ui::icon.domain'],
        'lucandidate' => ['LU Candidate', '/luCandidate', '','ui::icon.frame'],
        'constructicon' => ['Constructicon', '/constructicon', '','ui::icon.construction'],
        'reframing' => ['Reframing', '/reframing', '','ui::icon.lu'],
        'sentence' => ['Sentence', '/sentence', '','ui::icon.sentence'],
        'class' => ['Class', '/class', '','ui::icon.frame'],
        'cluster' => ['Cluster', '/cluster', '','ui::icon.cluster'],
        'microframe' => ['MicroFrame', '/microframe', '','ui::icon.microframe'],
        'grammar' => ['Grammar', '/parser/grammar', '','ui::icon.microframe','dev'],
        'construction' => ['Construction', '/parser/construction', '','ui::icon.microframe','dev'],
    ];

    $groups = [
        'frame' => ['title' => "Frame", "pages" => ['frame','reframing']],
//        'lexicon' => ['title' => "Lexicon", "pages" => ['lemma','form','lucandidate']],
        'lexicon' => ['title' => "Lexicon", "pages" => ['lucandidate','lemma']],
        'text' => ['title'=> "Text", "pages" => ['sentence']],
//        'construction' => ['title' => "Construction", "pages" => ['constructicon']],
        'ontology' => ['title' => "Ontology", "pages" => ['class','microframe','cluster']],
        'parser' => ['title' => "Parser", "pages" => ['grammar','construction'], "mode" => "dev"],
    ];
@endphp

<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['','Structure']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Structure
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    @foreach($groups as $group)
                        @php
                            $mode = $group["mode"] ?? 'prod';
                            if ((config('webtool.mode') == 'prod') && ($mode == 'dev')) {
                                continue;
                            }
                        @endphp
                        <div class="ui fluid card">
                            <div class="content  bg-gray-200">
                                <div class="header">
                                    {{$group['title']}}
                                </div>
                            </div>
                            <div class="content">
                                <div class="card-grid dense">
                                    @foreach($group['pages'] as $group)
                                        @php
                                            $item = $options[$group];
                                            $mode = $item[4] ?? 'prod';
                                            if ((config('webtool.mode') == 'prod') && ($mode == 'dev')) {
                                                continue;
                                            }
                                        @endphp
                                        <a
                                            class="ui card option-card"
                                            data-category="{{$group}}"
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
    </div>
</x-layout::index>
