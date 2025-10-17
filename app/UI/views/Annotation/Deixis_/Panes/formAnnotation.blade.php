<div class="ui form">
    <div class="bg-white pt-1 pr-1 pl-1 overflow-y-auto overflow-x-hidden">
        <div class="flex flex-row gap-1 justify-content-between pb-1">
            @if(is_null($object))
                <div class="ui label">
                    <div class="detail">#none</div>
                </div>
            @else
                <div class="ui label wt-tag-idObject">
                    <div class="detail" x-text="'#' + ($store.doStore.currentObject?.idObject || 'none')"></div>
                </div>
                <div>
                    <button
                        class="ui button basic secondary"
                        @click.prevent="annotation.video.gotoFrame(Alpine.store('doStore').currentStartFrame)"
                    >
                        <span x-text="'Start: ' + currentStartFrame"></span>
                    </button>
                    <button
                        class="ui button basic secondary"
                        @click.prevent="annotation.video.gotoFrame(Alpine.store('doStore').currentEndFrame)"
                    >
                        <span x-text="'End: ' + currentEndFrame"></span>
                    </button>
                    <button
                        class="ui button secondary"
                        @click.prevent="annotation.video.playByFrameRange({{$object->startFrame}},{{$object->endFrame}},0)"
                    ><i class="play icon"></i> Play Range
                    </button>
                    <button
                        class="ui button primary"
                        @click.prevent="annotation.objects.updateObjectRange()"
                    >Update Range
                    </button>
                </div>
                <div class="flex h-2rem gap-2">
                    <div class="ui label">
                        <div class="detail">{{$object->nameLayerType}}</div>
                    </div>
                    <div class="ui label wt-tag-id">
                        #{{$object->idDynamicObject}}
                    </div>
                </div>
                <div>
                    <button
                        id="btnClose"
                        class="ui medium icon button"
                        title="Close Object"
                        @click.prevent="annotation.objects.closeEdition()"
                    >
                        <i class="pt-1 close icon"></i>
                    </button>
                </div>
            @endif
        </div>
        @if(!is_null($object))
            <div class="fields">
                @if(!is_null($object->idGenericLabel) || ($object->layerGroup == 'Deixis'))
                    <div class="field  w-20rem mr-2">
                        <x-combobox.gl
                            id="idGenericLabel"
                            name="idGenericLabel"
                            label="Label"
                            :value="$object?->idGenericLabel ?? 0"
                            :idLayerType="$object?->idLayerType ?? 0"
                            :hasNull="false"
                        ></x-combobox.gl>
                    </div>
                @endif
                <div class="field mr-1">
                    <x-combobox.frame
                        id="idFrame"
                        label="Frame"
                        placeholder="Frame (min: 3 chars)"
                        style="width:250px"
                        class="mb-2"
                        :value="$object?->idFrame ?? 0"
                        :name="$object->frame ?? ''"
                        :hasDescription="false"
                        onSelect="htmx.ajax('GET','/annotation/dynamicMode/fes/' + result.idFrame,'#fes');"
                    ></x-combobox.frame>
                </div>
                <div id="fes" class="field w-15rem mr-1">
                    <x-combobox.fe-frame
                        id="idFrameElement"
                        name="idFrameElement"
                        label="FE"
                        :value="$object?->idFrameElement ?? 0"
                        :idFrame="$object?->idFrame ?? 0"
                        :hasNull="false"
                    ></x-combobox.fe-frame>
                </div>
            </div>
            <div class="fields">
                <div class="field mr-1">
                    <x-combobox.lu
                        id="idLU"
                        label="CV Name"
                        placeholder="(min: 3 chars)"
                        class="w-23rem mb-2"
                        :value="$object?->idLU"
                        :name="$object?->lu ?? ''"
                    ></x-combobox.lu>
                </div>
            </div>
        @endif
    </div>
    @if(!is_null($object))
        <div class="controls flex flex-row gap-1 justify-content-between">
            <div>
                @if(!is_null($object->idGenericLabel) || ($object->layerGroup == 'Deixis'))
                    <x-button
                        type="button"
                        label="Save"
                        onclick="annotation.objects.updateObjectAnnotation({idGenericLabel: $('#idGenericLabel').attr('value'),idLU: $('#idLU').attr('value'),idFrameElement: $('#idFrameElement').attr('value')})"
                        title="Save Object"
                    ></x-button>
                @endif
                @if(!is_null($object->idAnnotationLU)  || ($object->layerGroup == 'Deixis_lexical'))
                    <x-button
                        type="button"
                        label="Save"
                        onclick="annotation.objects.updateObjectAnnotation({idLU: $('#idLU').attr('value'),idFrameElement: $('#idFrameElement').attr('value'),})"
                        title="Save Object"
                    ></x-button>
                @endif
                <button
                    class="ui medium icon button negative"
                    @click.prevent="annotation.objects.deleteObject({{$object->idDynamicObject}})"
                    title="Delete Object"
                >
                    <i class="pt-1 trash alternate outline icon"></i>
                </button>
                <x-button
                    label="Comment"
                    color="secondary"
                    @click.prevent="Alpine.store('doStore').commentObject({{$object->idDynamicObject}})"
                ></x-button>
            </div>
            <div>
                <button
                    id="btnCreateObject"
                    class="ui button primary"
                    x-data @click="$store.doStore.createBBox()"
                >
                    <i class="plus square outline icon"></i>
                    Create BBox
                </button>
                <button
                    id="btnStartTracking"
                    class="ui button primary"
                    x-data @click="$store.doStore.startTracking()"
                >
                    <i class="play icon"></i>
                    Start
                </button>
                <button
                    id="btnPauseTracking"
                    class="ui button primary"
                    x-data @click="$store.doStore.pauseTracking()"
                >
                    <i class="pause icon"></i>
                    Pause
                </button>
                <button
                    id="btnStopObject"
                    class="ui button primary"
                    x-data @click="$store.doStore.stopTracking()"
                >
                    <i class="window stop icon"></i>
                    <span x-data x-text="'Stop at #' + ($store.doStore.currentFrame || '')"></span>
                </button>
                <button
                    id="btnDeleteBBox"
                    class="ui medium icon button negative"
                    title="Delete BBoxes from Object"
                    @click.prevent="annotation.objects.deleteBBox({{$object->idDynamicObject}})"
                >
                    <i class="pt-1 trash alternate outline icon"></i>
                </button>

            </div>
        </div>
    @endif
</div>

