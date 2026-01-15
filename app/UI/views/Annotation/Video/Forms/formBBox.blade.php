<div class="ui card form-card w-full p-1">
    <div class="content">
        <div class="d-flex justify-between">
            <div class="d-flex">
                <div x-show="!bboxDrawn">
{{--                    <div x-show="currentFrame === {!! $object->startFrame !!}">--}}
                        <button
                            id="btnCreateObject"
{{--                            class="ui button primary {!! $object->hasBBoxes ? 'disabled' : '' !!}"--}}
                            class="ui button primary"
                            @click="$dispatch('bbox-create')"
                        >
                            <i class="plus square outline icon"></i>
                            Create BBox
                        </button>
{{--                    </div>--}}
                </div>
                <div
                    x-show="bboxDrawn"
                >
                    <button
                        class="ui button primary toggle"
                        @click="$dispatch('bbox-toggle-tracking')"
                    >
                        <i :class="autoTracking ? 'stop icon' : 'play icon'"></i>
                        <span x-text="autoTracking ? 'Stop tracking' : 'Autotracking'"></span>
                    </button>
                </div>
            </div>
            <div x-show="bboxDrawn">
                <div
                    class="ui checkbox"
                    x-init="$($el).checkbox()"
                    @click="$dispatch('bbox-change-blocked')"
                >
                    <input
                        type="checkbox"
                        tabindex="0"
                        :checked="bboxDrawn && (bboxDrawn.blocked === 1)"
                    >
                    <label class="pl-6">is blocked?</label>
                </div>
            </div>
            <div x-show="bboxDrawn">
                <button
                    id="btnDeleteBBox"
                    class="ui medium icon button negative"
                    :class="autoTracking && 'disabled'"
                    title="Delete BBoxes from Object"
                    @click.prevent="messenger.confirmDelete('Removing all BBoxes of object #{{$object->idObject}}.', '/annotation/{{$object->annotationType}}/deleteAllBBoxes/{{$object->idDocument}}/{{$object->idObject}}')"
                >
                    <i class="trash alternate outline icon"></i>
                    Delete All BBoxes
                </button>
            </div>
        </div>
        <div class="d-flex pt-3">
            <div class="ui label">Current BBox: <span
                    x-text="bboxDrawn ? '#' + bboxDrawn.idBoundingBox : 'none' "></span></div>
            <div class="ui label" x-show="bboxDrawn" x-text="bboxDrawn && 'frame: ' + bboxDrawn.frameNumber"></div>
            <div class="ui label" x-show="bboxDrawn" x-text="bboxDrawn && 'x: ' + bboxDrawn.x"></div>
            <div class="ui label" x-show="bboxDrawn" x-text="bboxDrawn && 'y: ' + bboxDrawn.y"></div>
            <div class="ui label" x-show="bboxDrawn" x-text="bboxDrawn && 'width: ' + bboxDrawn.width"></div>
            <div class="ui label" x-show="bboxDrawn" x-text="bboxDrawn && 'height: ' + bboxDrawn.height"></div>
            <div class="ui red basic label" x-show="bboxDrawn && bboxDrawn?.isGroundTruth">isGroundTruth</div>
            <div class="ui blue basic label" x-show="bboxDrawn && !bboxDrawn?.isGroundTruth">automatic</div>
        </div>
    </div>
</div>

