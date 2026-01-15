<div class="columnLayersFlex">
    <div></div>
    @php
        $countLines = 0;
    @endphp
    @foreach(($layers ?? []) as $layerEntry => $layer)
        @php
            $count = empty($groupedLayers[$layerEntry] ?? []) ? 1 : count($groupedLayers[$layerEntry]);
            $countLines += $count;
        @endphp
        @for($i = 0; $i < $count; $i++)
            <div>{!! $layer->name !!}</div>
        @endfor
    @endforeach
</div>
<div class="columnAnnotation">
    <div class="rowWord">
        @foreach($words as $i => $w)
            @php
               $height = 24;
            @endphp
            <div
                class="{!! $w['isPunct'] ? 'colSpace' : 'colWord' !!}"
                id="word_{{$i}}"
                data-type="word"
                data-i="{{$i}}"
                data-startchar="{{$w['startChar']}}"
                data-endchar="{{$w['endChar']}}"
                style="height:{{$height}}px"
            >{!! $w['word'] ?? ' '!!}
            </div>
        @endforeach
    </div>
    <div class="rowAnnotation" style="height:{!!  24 * $countLines !!}px;position:relative;">
        @foreach($words as $i => $w)
            <div class="{!! $w['isPunct'] ? 'colSpace' : 'colWord' !!}">
                <span class="wordHidden">{{$w['word']}}</span>
            </div>
        @endforeach
        @php
            $topLine = 0;
        @endphp
        @foreach(($layers ?? []) as $layerEntry => $layer)
            @if($layerEntry != 'lty_target')
                @if(empty($groupedLayers[$layerEntry] ?? []))
                    @php
                        $topLine += 24;
                    @endphp
                @else
                    @foreach($groupedLayers[$layerEntry] as $line => $objects)
                        <div
                            class="rowObject"
                            style="top:{{$topLine}}px;position:absolute;"
                        >
                            @foreach($objects as $object)
                                @php
                                    $left = 10.5 * $object->startChar;
                                    $width = 10.5 * ($object->endChar - $object->startChar + 1);
                                @endphp
                                <span
                                    class="color_{{$object->idColor}} feLabel"
                                    style="left:{{$left}}px;width:{{$width}}px;"
                                    title="{{$object->name}}"
                                >{{$object->name}}</span>
                            @endforeach
                        </div>
                        @php
                            $topLine += 24;
                        @endphp
                    @endforeach
                @endif
            @endif
        @endforeach
    </div>

</div>
