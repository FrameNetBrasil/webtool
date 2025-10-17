@php
    $sections = [['/','Home'],['/annotation','Annotation']];
    if ($annotationType == 'staticBBox') {
        $sections[] = ['/annotation/staticBBox','Static BBox'];
    } elseif ($annotationType == 'staticEvent') {
        $sections[] = ['/annotation/staticEvent','Static event'];
    }
    $sections[] = ['',$image->name];
@endphp
<x-layout::index>
    <script type="text/javascript" src="/annotation/image/script/objects"></script>
    <script type="text/javascript" src="/annotation/image/script/components"></script>
    <div class="app-layout annotation-static-bbox">
        <x-layout::breadcrumb
            :sections="$sections"
        ></x-layout::breadcrumb>
        <div class="annotation-canvas">
            <div class="annotation-figure">
                @include("Annotation.Image.Panes.figure")
            </div>
            <div class="annotation-data">
                <div class="annotation-info">
                    <div class="tag">
                        <div class="ui label wt-tag-id">
                            Corpus: {{$corpus->name}}
                        </div>
                        <div class="ui label wt-tag-id">
                            Document: {{$document->name}}
                        </div>
                    </div>
                    <div>
                        @if($idPrevious)
                            <a href="/annotation/{{$annotationType}}/{{$idPrevious}}">
                                <button class="ui left labeled icon button">
                                    <i class="left arrow icon"></i>
                                    Previous
                                </button>
                            </a>
                        @endif
                        @if($idNext)
                            <a href="/annotation/{{$annotationType}}/{{$idNext}}">
                                <button class="ui right labeled icon button">
                                    <i class="right arrow icon"></i>
                                    Next
                                </button>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="annotation-forms">
                    @include("Annotation.Image.Panes.forms")
                </div>
                <div class="annotation-objects">
                    @include("Annotation.Image.Panes.grids")
                </div>
            </div>
        </div>
    </div>
</x-layout::index>
