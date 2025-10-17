
<div id="outerForm" class="fade-me-out" style="height:300px;width:800px;padding:8px;">
        <div class="ui form">
                <div class="bg-white pt-1 pr-1 pl-1 overflow-y-auto overflow-x-hidden">
                    <div class="flex flex-row gap-1 justify-content-between pb-1">
                        @if(is_null($object))
                            <div class="font-bold">Current Object: #none</div>
                        @else
                            <div
                                class="font-bold"
                            >Current Object #<span x-text="$store.doStore.currentObject?.idObject || 'none'"></span>
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
                            </div>
                            <div class="flex h-2rem gap-2">
                                <div class="ui label">
                                    <div class="detail">{{$object->nameLayerType}}</div>
                                </div>
                                <div class="ui label wt-tag-id">
                                    #{{$object->idDynamicObject}}
                                </div>
                            </div>
                        @endif
                    </div>
                    @if(!is_null($object))
                        <div class="fields">
                            <div class="field mr-1">
                                ssss
                            </div>
                        </div>
                    @endif
                </div>
                @if(!is_null($object))
                    <div class="controls flex flex-row gap-1 justify-content-between">
                        <div>
                            <x-button
                                label="Back"
                                color="secondary"
                                hx-get="/sandbox/page1"
                                hx-swap="outerHTML"
                                hx-target="#outerForm"
                            ></x-button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
<style>
    .fade-me-out.htmx-swapping {
        opacity: 0;
        transition: opacity 1s ease-out;
    }
</style>
