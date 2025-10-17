@php
    $originalWidth = intval($image->width);
    $originalHeight = intval($image->height);
    $canvasWidth = 860;
    $canvasHeight = 700;
    $scaleWidth = $canvasWidth / $originalWidth;
    $scaleHeight = $canvasHeight / $originalHeight;
    $scale = ($scaleHeight < $scaleWidth) ? $scaleHeight : $scaleWidth;
    $imageWidth = intval($originalWidth * $scale);
    $imageHeight = intval($originalHeight * $scale);
    debug("original width: ". $originalWidth);
    debug("original height: ". $originalHeight);
    debug("canvas width: ". $canvasWidth);
    debug("canvas height: ". $canvasHeight);
    debug("scale width: ". $scaleWidth);
    debug("scale height: ". $scaleHeight);
    debug("scale: ". $scale);
    debug("image width: ". $imageWidth);
    debug("image height: ". $imageHeight);
@endphp
<x-layout.annotation>
    <x-slot:head>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/annotation/staticBBox','Static BBox Annotation'],['',$document->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div
            id="staticBBoxAnnotationPane"
            class="staticBBoxAnnotationPane"
            {{--            x-data="$store.doStore"--}}
        >
            <div class="west">
                <div class="controls">
                    @include("Annotation.StaticBBox.Panes.controlsPane")
                </div>
                <div class="image">
                    @include("Annotation.StaticBBox.Panes.imagePane")
                </div>
                {{--                <div class="comment">--}}
                {{--                    @include("Annotation.StaticBBox.Panes.commentPane")--}}
                {{--                </div>--}}
            </div>
            <div class="center">
                <div class="header flex w-full">
                    <div class="font-bold">
                        <x-icon.image></x-icon.image>{{$image->name}}
                    </div>
                    <div class="flex flex-grow-1 justify-content-end">
                        <div class="tag pr-2">
                            <div class="ui label wt-tag-id">
                                #{{$idDocument}}
                            </div>
                        </div>
                        @if($idPrevious)
                            <x-element.previous
                                url="/annotation/staticBBox/{{$idPrevious}}"
                            ></x-element.previous>
                            <div class="w-1rem">&nbsp;</div>
                        @endif
                        @if($idNext)
                            <x-element.next url="/annotation/staticBBox/{{$idNext}}"
                            ></x-element.next>
                        @endif
                    </div>
                </div>
                <div class="flex flex-column flex-grow-1 pt-2">
                    <div
                        id="formObject"
                        class="form"
                        hx-trigger="load"
                        hx-get="/annotation/staticBBox/formObject/0/0"
                    >
                    </div>
                    @include("Annotation.StaticBBox.Panes.gridsPane")
                </div>
                <script type="text/javascript">
                    window.annotation = {
                        _token: "{{ csrf_token() }}",
                        document: {{ Js::from($document) }},
                        image: {{ Js::from($image) }},
                        objectList: [],
                        idStaticObject:{!! $idStaticObject ?? 'null' !!},
                        idDocument:{!! $document->idDocument ?? 'null' !!},
                        dimensions: {
                            canvasWidth: {{$canvasWidth}},
                            canvasHeight: {{$canvasHeight}},
                            imageWidth: {{$imageWidth}},
                            imageHeight: {{$imageHeight}},
                            scale: {{$scale}}
                        }
                    };

                    document.body.addEventListener("updateObjectAnnotationEvent", function(evt){
                        console.log("event updateObjectAnnotationEvent");
                        annotation.objects.updateObjectAnnotationEvent();
                    })

                    @include("Annotation.StaticBBox.Scripts.api")
                    @include("Annotation.StaticBBox.Scripts.image")
                    @include("Annotation.StaticBBox.Scripts.objects")
                    @include("Annotation.StaticBBox.Scripts.drawBox")
                    @include("Annotation.StaticBBox.Scripts.store")
                    @include("Annotation.StaticBBox.Scripts.staticBBoxObject")
                    @include("Annotation.StaticBBox.Scripts.boundingBox")
                </script>

            </div>
        </div>
    </x-slot:main>
</x-layout.annotation>


