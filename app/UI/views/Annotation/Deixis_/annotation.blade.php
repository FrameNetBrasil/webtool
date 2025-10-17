<x-layout.annotation>
    <x-slot:head>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/annotation/deixis','Deixis Annotation'],['',$document->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div
            id="deixisAnnotationPane"
            class="deixisAnnotationPane"
            x-data="$store.doStore"
        >
            <div class="north">
                <div class="west">
                    <div class="video">
                        @include("Annotation.Deixis.Panes.videoPane")
                    </div>
                    <div class="flex-grow-1">
                        <div
                            id="formObject"
                            class="form"
                            hx-trigger="load"
                            hx-get="/annotation/deixis/formAnnotation/0"
                        >
                        </div>
                    </div>
                </div>
                <div class="east">
                    <div class="header flex w-full">
                        <div class="font-bold">
                            <x-icon.video></x-icon.video>{{$video->title}}
                        </div>
                        <div class="flex flex-grow-1 justify-content-end">
                            <div class="tag pr-2">
                                <div class="ui label wt-tag-id">
                                    #{{$idDocument}}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-column flex-grow-0 pt-2">
                        @include("Annotation.Deixis.Panes.newObject")
                    </div>
                    @include("Annotation.Deixis.Panes.gridsPane")

                </div>
            </div>
        </div>
        <script type="text/javascript" src="/scripts/vatic/dist/compatibility.js"></script>
        <script type="text/javascript" src="/scripts/vatic/dist/jszip.js"></script>
        <script type="text/javascript" src="/scripts/vatic/dist/StreamSaver.js"></script>
        <script type="text/javascript" src="/scripts/vatic/dist/polyfill.js"></script>
        <script type="text/javascript" src="/scripts/vatic/dist/jsfeat.js"></script>
        <script type="text/javascript" src="/scripts/vatic/dist/nudged.js"></script>
        <script type="text/javascript" src="/scripts/vatic/dist/pouchdb.min.js"></script>
        <script type="text/javascript" src="/scripts/vatic/vatic.js"></script>
        <script type="text/javascript" src="/scripts/vatic/FramesManager.js"></script>
        <script type="text/javascript" src="/scripts/vatic/OpticalFlow.js"></script>
        <script type="text/javascript" src="/scripts/vatic/BoundingBox.js"></script>
        {{--                    <script type="text/javascript" src="/scripts/vatic/DynamicObject.js"></script>--}}
        <script type="text/javascript" src="/scripts/vatic/ObjectsTracker.js"></script>
        <script type="text/javascript">
            window.annotation = {
                _token: "{{ csrf_token() }}",
                document: {{ Js::from($document) }},
                video: {{ Js::from($video) }},
                layerList: [],
                idDynamicObject:{!! $idDynamicObject ?? 'null' !!}
            };

            document.body.addEventListener("updateObjectAnnotationEvent", function(evt) {
                annotation.objects.updateObjectAnnotationEvent();
            });

            document.body.addEventListener("htmx:afterSwap", function(elt) {
                if (elt.target.id === "formObject"){
                    if (document.getElementById("btnCreateObject")) {
                        Alpine.store("doStore").uiEditingObject();
                    }
                }
            });

            @include("Annotation.Deixis.Scripts.DeixisObject")
            @include("Annotation.Deixis.Scripts.api")
            @include("Annotation.Deixis.Scripts.video")
            @include("Annotation.Deixis.Scripts.drawBox")
            @include("Annotation.Deixis.Scripts.objects")
            @include("Annotation.Deixis.Scripts.timeline")
            @include("Annotation.Deixis.Scripts.store")
        </script>
    </x-slot:main>
</x-layout.annotation>


