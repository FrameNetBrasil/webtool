<div class="flex flex-row">
    <div class="annotationNI" style="width:150px">
        <div class="rowNI">
            <template x-for="itType in asData.it">
                <template x-if="(itType.entry != 'int_normal') && (itType.entry != 'int_apos')">
                    <div
                        class="colNI"
                    >
                        <div
                            class="ni"
                            data-type="ni"
                            :data-name="itType.name"
                            :data-id="itType.idTypeInstance"
                            x-text="itType.name"
                            >
                        </div>
                        <div
                            x-data="{get nis() {return asData.nis[itType.idInstantiationType];}}"
                            >
                            <template x-for="ni,index in nis">
                                <div class="label">
                                    <div
                                        :class="'line color_' + ni.idColor"
                                    >
                                        <span
                                        :class="'feLabel color_' + ni.idColor"
                                        style="top:0"
                                            x-text="ni.label"
                                        ></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </template>
        </div>
    </div>
    <div
        class="layers"
    >
        <div>&nbsp;</div>
        <template x-for="layerType,index in asData.layerTypes">
            <div
                x-data="{topLine: (index * 24) + 21}"
                x-text="layerType.name"
                 ></div>
        </template>
    </div>
    <div
        class="annotationSentence flex flex-column" x-data="asData"
    >
        <div class="rowWord">
            @foreach($words as $i => $word)
                <div class="{!! ($word['word'] != ' ') ? 'colWord' : 'colSpace' !!}">
                    @php($isTarget = ($i >= $target->startWord) && ($i <= $target->endWord))
                    @php($labelsAtWord = ($spans[$i] ?? []))
                    @php($height = 24)
                    <span
                        class="word {{$isTarget ? 'target' : ''}} {{$word['hasFE'] ? 'hasFE' : ''}}"
                        id="word_{{$i}}"
                        data-type="word"
                        data-i="{{$i}}"
                        data-startchar="{{$word['startChar']}}"
                        data-endchar="{{$word['endChar']}}"
                        style="height:{{$height}}px"
                    >{!! ($word['word'] != ' ') ? $word['word'] : '&nbsp;' !!}
                    </span>
                </div>
            @endforeach
        </div>
        <div class="rowAnnotation">

            <template x-for="word,index in asData.words">
                <div
                    :class="'flex flex-column ' + ((word.word == ' ') ? 'colSpace' : 'colWord')"
                >
                    <div
                        style="height:0;overflow-y:hidden"
                        x-text="(word.word == ' ') ? '&nbsp;' : word.word"
                    >
                    </div>
                    <template x-for="layerType in asData.layerTypes">
                        <div class="label" x-data="{get span() {return this.asData.spans[index][layerType.idLayer]}}">
                            <template x-if="span !== null">
                                <div
                                >
                                    <div
                                        :class="'line color_' + span.idColor"
                                    >
                                        <span
                                            x-show="span.label"
                                            :class="'feLabel color_' + span.idColor"
                                            style="top:0"
                                            x-text="span.label"
                                        >
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
