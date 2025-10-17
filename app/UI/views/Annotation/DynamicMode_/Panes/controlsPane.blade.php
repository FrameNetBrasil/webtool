<div class="controls flex flex-row gap-1 justify-content-between mr-5">
    <div class="flex flex-column">
        <div class="mr-1 font-bold border-bottom-1">
            Object
        </div>
        <div class="mt-1">
            <button
                id="btnCreateObject"
                class="ui button primary"
                x-data @click="$store.doStore.createObject()"
            >
                <i class="plus square outline icon"></i>
                Create
            </button>
        </div>
    </div>
    <div class="flex flex-column">
        <div class="mr-1 font-bold border-bottom-1">
            Tracking
        </div>
        <div class="mt-1">
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
                <span x-data x-text="'Stop at frame #' + ($store.doStore.currentFrame || '')"></span>
            </button>
{{--            <button--}}
{{--                id="btnPauseTracking"--}}
{{--                class="ui button primary"--}}
{{--                x-data @click="$store.doStore.pauseTracking()"--}}
{{--            >--}}
{{--                <i class="pause icon"></i>--}}
{{--                Pause--}}
{{--            </button>--}}
{{--            <button--}}
{{--                id="btnEndObject"--}}
{{--                class="ui button primary"--}}
{{--                x-data @click="$store.doStore.endObject()"--}}
{{--            >--}}
{{--                <i class="window close outline icon"></i>--}}
{{--                <span x-data x-text="'End at frame #' + ($store.doStore.currentFrame || '')"></span>--}}
{{--            </button>--}}
        </div>
    </div>
    <div class="flex flex-column">
        <div class="mr-1 font-bold">
            &nbsp;
        </div>
        <div class="mr-1 font-bold">
            <button
                id="btnShowHideObjects"
                class="ui toggle button secondary"
                x-data @click="$store.doStore.showHideObjects()"
            >
                Show/Hide All
            </button>
            <button
                id="btnClear"
                class="ui button secondary"
                x-data @click="$store.doStore.clear()"
            >
                <i class="redo icon"></i>
                Clear
            </button>
        </div>
    </div>
</div>
