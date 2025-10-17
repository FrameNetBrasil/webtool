<x-layout.edit>
    <x-slot:head>
        <x-layout::breadcrumb
                :sections="[['/','Home'],['/annotation/fe','FE Annotation'],['',$document->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        @include('Annotation.FE_41.Panes.annotation')
        <div id="feAnnotationPane" class="feAnnotationPane flex flex-column h-full">
            <div class="header">
                <div class="tag">
                    <div class="ui label wt-tag-id">
                        {{$corpus->name}}
                    </div>
                    <div class="ui label wt-tag-id">
                        {{$document->name}}
                    </div>
                    <div class="ui label wt-tag-id">
                        #{{$idDocumentSentence}}
                    </div>
                </div>
                <div class="navigation">
                    @if($idPrevious)
                        <div class="previous">
                            <x-element.previous url="/annotation/fe/sentence/{{$idPrevious}}"></x-element.previous>
                        </div>
                    @endif
                    @if($idNext)
                        <div class="next">
                            <x-element.next url="/annotation/fe/sentence/{{$idNext}}"></x-element.next>
                        </div>
                    @endif
                </div>
            </div>
            <div class="annotations flex-grow-1">
                @include('Annotation.FE_41.Panes.annotations')
            </div>
        </div>
    </x-slot:main>
</x-layout.edit>
