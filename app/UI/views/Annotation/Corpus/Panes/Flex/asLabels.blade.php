<div class="annotationTab">
    {{--    <div id="annotationTabs_{{$idAnnotationSet}}" class="ui pointing secondary menu tabs">--}}
    {{--        @foreach($layers as $layerTypeEntry => $layer)--}}
    {{--            @if(($layerTypeEntry != 'lty_target') && ($layerTypeEntry != 'lty_fe'))--}}
    {{--                <a--}}
    {{--                        class="item"--}}
    {{--                        data-tab="{{$layerTypeEntry}}"--}}
    {{--                >{{$layer->name}}</a>--}}
    {{--            @endif--}}
    {{--        @endforeach--}}
    {{--    </div>--}}
    <div class="d-flex flex-col">
        @foreach($layers as $layerTypeEntry => $layer)
            <div class="font-semibold pb-2 border-b">
                {{$layer->name}}
            </div>
            <hr>

                <div class="rowFE">
                    @foreach($glsByLayerType[$layerTypeEntry] as $gl)
                        <div class="colFE">
                            <button
                                class="ui right labeled icon button mb-2 color_{{$gl->idColor}}"
                                @click.stop="onLabelAnnotate({{$gl->idEntity}})"
                            >
                                <i
                                    class="delete icon"
                                    @click.stop="onLabelDelete({{$gl->idEntity}})"
                                >
                                </i>
                                <div class="d-flex">
                                    {{$gl->name}}
                                </div>
                            </button>
                        </div>
                    @endforeach
                </div>
        @endforeach
    </div>

    {{--    <script type="text/javascript">--}}
    {{--        $(function () {--}}
    {{--            $(".menu .item").tab();--}}
    {{--        });--}}
    {{--    </script>--}}
</div>
