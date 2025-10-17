<x-layout.annotation>
    <x-slot:head>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/annotation/dynamicMode','Dynamic Annotation'],['',$document->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div
            id="dynamicAnnotationPane"
            class="dynamicAnnotationPane w-full"
            @keydown.arrow-left="console.log('1')"
            @keydown.arrow-right="console.log('2')"
            @keydown.enter="console.log('3')"
        >
            <div class="west">
                <div class="video">
                    @include("Annotation.DynamicMode.Panes.videoPane")
                </div>
                <div class="controls">
                    @include("Annotation.DynamicMode.Panes.controlsPane")
                </div>
{{--                <div class="comment">--}}
{{--                    @include("Annotation.DynamicMode.Panes.commentPane")--}}
{{--                </div>--}}
            </div>
            <div class="center w-full" style="width:1024px;" >
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
                        <div>
                            <x-link-button
                                id="btnBuildSentences"
                                label="Build Sentences"
                                color="secondary"
                                href="/annotation/dynamicMode/buildSentences/{{$idDocument}}"
                            >
                            </x-link-button>
                        </div>
                    </div>
                </div>
                <div class="flex flex-column flex-grow-1 pt-2" x-data="$store.doStore">
                    <div
                        id="formObject"
                        class="form"
                        hx-trigger="load"
                        hx-get="/annotation/dynamicMode/formObject/0/0"
                    >
                    </div>
                    @include("Annotation.DynamicMode.Panes.gridsPane")
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
{{--                <script type="text/javascript" src="/scripts/vatic/Frame.js"></script>--}}
                <script type="text/javascript" src="/scripts/vatic/DynamicObject.js"></script>
                <script type="text/javascript" src="/scripts/vatic/ObjectsTracker.js"></script>
                <script type="text/javascript">
                    window.annotation = {
                        _token: "{{ csrf_token() }}",
                        document: {{ Js::from($document) }},
                        video: {{ Js::from($video) }},
                        objectList: [],
                        idDynamicObject:{!! $idDynamicObject ?? 'null' !!}
                    };

                    document.body.addEventListener("updateObjectAnnotationEvent", function(evt){
                        console.log("event updateObjectAnnotationEvent");
                        annotation.objects.updateObjectAnnotationEvent();
                    })

                    @include("Annotation.DynamicMode.Scripts.api")
                    @include("Annotation.DynamicMode.Scripts.video")
                    @include("Annotation.DynamicMode.Scripts.drawBox")
                    @include("Annotation.DynamicMode.Scripts.objects")
                    @include("Annotation.DynamicMode.Scripts.store")
                </script>

            </div>
        </div>
    </x-slot:main>
</x-layout.annotation>


