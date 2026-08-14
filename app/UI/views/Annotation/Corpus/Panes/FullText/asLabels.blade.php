<div class="annotationTab">
    <div id="annotationTabs_{{$idAnnotationSet}}" class="ui pointing secondary menu tabs">
        <a
                class="active item"
                data-tab="labels"
        >FE</a>
        @foreach($layers as $layerTypeEntry => $layer)
            @if(($layerTypeEntry != 'lty_target') && ($layerTypeEntry != 'lty_fe'))
                <a
                        class="item"
                        data-tab="{{$layerTypeEntry}}"
                >{{$layer->name}}</a>
            @endif
        @endforeach
        <a
                class="item"
                data-tab="comment"
        ><i class="comment dots outline icon"></i>
            Comment</a>
    </div>
    <div
            class="ui tab"
            data-tab="labels"
    >
        @foreach($fesByType as $type => $fesData)
            @if (count($fesData) > 0)
                <div>{{$type}}</div>
                <div class="rowFE">
                    @foreach($fesData as $idEntity)
                        @php($fe = $fes[$idEntity])
                        <div class="colFE">
                            <button
                                    class="ui right labeled icon button mb-2 color_{{$fe->idColor}}"
                                    @click.stop="onLabelAnnotate({{$fe->idEntity}})"
                            >
                                <i
                                        class="delete icon"
                                        @click.stop="onLabelDelete({{$fe->idEntity}})"
                                >
                                </i>
                                <x-element::fe :type="$fe->coreType" name="{{$fe->name}}" idColor="{{$fe->idColor}}"></x-element::fe>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
    @foreach($layers as $layerTypeEntry => $layer)
        @if(($layerTypeEntry != 'lty_target') && ($layerTypeEntry != 'lty_fe'))
            <div
                    class="ui tab"
                    data-tab="{{$layerTypeEntry}}"
            >

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
            </div>
        @endif
    @endforeach

    <script type="text/javascript">
        $(function () {
            $(".menu .item").tab();

            function resizeAnnotationTabPanes() {
                $(".annotationTab").each(function () {
                    var $menu = $(this).children(".ui.pointing.secondary.menu.tabs").first();
                    if (!$menu.length) {
                        return;
                    }
                    var top = $menu[0].getBoundingClientRect().bottom;
                    // 48px accounts for the trailing bottom padding of ancestor
                    // containers (.twelve.wide.column + .annotation-workarea)
                    // that sit below this pane but outside its own box.
                    var maxHeight = Math.max($(window).height() - top - 48, 100);
                    $(this).children(".ui.tab").css("max-height", maxHeight + "px");
                });
            }

            resizeAnnotationTabPanes();
            $(window).on("resize", resizeAnnotationTabPanes);
        });
    </script>
</div>
