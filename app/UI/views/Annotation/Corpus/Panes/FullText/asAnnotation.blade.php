<div class="columnNI">
    <div class="rowNI">
        @foreach($it as $i => $type)
            @if(($type->entry != 'int_normal') && ($type->entry != 'int_apos'))
                <div class="colNI">
                        <span
                                class="ni"
                                id="ni_{{$i}}"
                                data-type="ni"
                                data-name="{{$type->name}}"
                                data-id="{{$type->idInstantiationType}}"
                                @click="onSelectNI($el)"
                        >{{$type->name}}
                        </span>
                </div>
                @foreach(($groupedLayers['nis'] ?? []) as $idInstantiationType => $niFEs)
                    @if($type->idInstantiationType == $idInstantiationType)
                        @foreach($niFEs as $niFE)
                            <div
                                    class="colNILabel"
                            >
                                    <span
                                            class="feLabel color_{{$fes[$niFE->idEntity]->idColor}}"
                                    >{{$niFE->name}}
                                    </span>
                            </div>
                        @endforeach
                    @endif
                @endforeach
            @endif
        @endforeach
    </div>

</div>
<div class="columnLayers">
    <div></div>
    @php
        $countLines = 0;
    @endphp
    @foreach(($layers ?? []) as $layerEntry => $layer)
        @if($layerEntry != 'lty_target')
            @php
                $count = empty($groupedLayers[$layerEntry] ?? []) ? 1 : count($groupedLayers[$layerEntry]);
                $countLines += $count;
            @endphp
            @for($i = 0; $i < $count; $i++)
                <div>{!! $layer->name !!}</div>
            @endfor
        @endif
    @endforeach
</div>
<div class="columnAnnotation">
    <div class="rowWord">
        @foreach($words as $i => $w)
            @php
                $target = $groupedLayers['lty_target'][0][0];
                $isTarget = ($i >= $target->startWord) && ($i <= $target->endWord);
                // height = número de linhas para lty_fe
                $lines = count($groupedLayers['lty_target']);
                $height = 24;// + ($isTarget ? 0 : ($lines * 30))
            @endphp
            <div
                class="{!! $w['isPunct'] ? 'colSpace' : 'colWord' !!} word{{$isTarget ? ' target' : ''}}{{$w['hasFE'] ? ' hasFE' : ''}}"
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
<div hx-swap-oob="outerHTML:#annotationSetStatus">
    @include("Annotation.Corpus.Panes.asStatus")
</div>
<div hx-swap-oob="innerHTML:#statusField">
    @include("Annotation.Corpus.Panes.asStatusField")
</div>

