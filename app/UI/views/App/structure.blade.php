@php
    $options = [
        'frame' => ['Frame', '/frame', '','ui::icon.frame'],
        'reframing' => ['Reframing', '/reframing', '','ui::icon.lu'],
        'lemma' => ['Lemmas', '/lemma', '','ui::icon.domain'],
    ];

    $groups = [
        'frame' => ['title' => "Frame", "pages" => ['frame','reframing']],
        'lexicon' => ['title' => "Lexicon", "pages" => ['lemma']],
    ];
@endphp

<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','Structure']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Structure
                        </div>
                    </div>
                </div>
                <div class="page-content grid-page">
                    <div class="ui container">
                        @foreach($groups as $group)
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
                                            @endphp
                                            <a
                                                class="ui card option-card"
                                                data-category="{{$group}}"
                                                href="{{$item[1]}}"
                                                hx-boost="true"
                                            >
                                                <div class="content">
                                                    <div class="header">
                                                        <x-dynamic-component :component="$item[3]"/>
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
            </div>
        </main>
    </div>
</x-layout::index>
