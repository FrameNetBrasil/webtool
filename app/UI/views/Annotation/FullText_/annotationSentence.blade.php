<x-layout.edit>
    <x-slot:head>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/annotation/fullText','FullText Annotation'],['',$document->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        @include('Annotation.FullText.Panes.annotation')
        <div id="fullTextAnnotationPane" class="fullTextAnnotationPane flex flex-column h-full">
                <div class="header">
                    <div class="tag">
                        <div class="ui label wt-tag-id">
                            {{$corpus->name}}
                        </div>
                        <div class="ui label wt-tag-id">
                            <a href="/annotation/fullText/{{$document->idDocument}}">{{$document->name}}</a>
                        </div>
                        <div class="ui label wt-tag-id">
                            #{{$idDocumentSentence}}
                        </div>
                    </div>
                    <div class="navigation">
                        @if($idPrevious)
                            <div class="previous">
                                <x-element.previous url="/annotation/fullText/sentence/{{$idPrevious}}"></x-element.previous>
                            </div>
                        @endif
                        @if($idNext)
                            <div class="next">
                                <x-element.next url="/annotation/fullText/sentence/{{$idNext}}"></x-element.next>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="annotations flex-grow-1" >
                    @include('Annotation.FullText.Panes.annotations')
                </div>
        </div>
    </x-slot:main>
</x-layout.edit>
