@php
    $panelWestWidth = $image->width + 30;
    $panelImageHeight = $image->height + 40;
@endphp
<x-layout.edit>
    <x-slot:head>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/annotation/staticEvent','StaticEvent Annotation'],['',$document->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        @include('Annotation.StaticEvent.Panes.annotation')
        <div id="staticEventAnnotationPane" class="staticEventAnnotationPane">
            <div class="west">
                <div class="image">
                    @include('Annotation.StaticEvent.Panes.imagePane')
                </div>
                <div class="sentence">
                    @if($type != 'no sentence')
                        @include('Annotation.StaticEvent.Panes.sentencePane')
                    @endif
                </div>
{{--                <div class="comment">--}}
{{--                    @include('Annotation.StaticEvent.Panes.commentPane')--}}
{{--                </div>--}}
            </div>
            <div class="center">
                <div class="header">
                    <div class="tag">
                        <div class="ui label wt-tag-id">
                            {{$corpus->name}}
                        </div>
                        <div class="ui label wt-tag-id">
                            {{$document->name}}
                        </div>
                        <div class="ui label wt-tag-id">
                            <x-icon.image></x-icon.image>{{$image->name}}
                        </div>
                        <div class="ui label wt-tag-id">
                            #{{$idDocumentSentence}}
                        </div>
                    </div>
                    <div class="navigation">
                        @if($idPrevious)
                            <div class="previous">
                                <x-element.previous
                                    url="/annotation/staticEvent/sentence/{{$idPrevious}}"></x-element.previous>
                            </div>
                        @endif
                        @if($idNext)
                            <div class="next">
                                <x-element.next url="/annotation/staticEvent/sentence/{{$idNext}}"></x-element.next>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="annotation">
                    @include('Annotation.StaticEvent.Panes.framePane')
                </div>
            </div>
        </div>
    </x-slot:main>
</x-layout.edit>
