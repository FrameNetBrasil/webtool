@php
    $options = [
        'importfulltext' => ['Import FullText', '/utils/importfulltext', '','ui::icon.frame'],
    ];
@endphp

<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','Utils']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="page-content">
                <div class="ui container">
                    <div class="card-grid dense">
                        @foreach($options as $category => $option)
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
