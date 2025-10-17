<div class="controls flex flex-row gap-1 justify-content-between mr-5 mt-5">
    <div class="flex flex-column">
        <div class="mr-1 font-bold border-bottom-1">
            BBox
        </div>
        <div class="mt-1">
            <button
                id="btnCreateObject"
                class="ui button primary"
                x-data @click="$store.doStore.createBBox()"
            >
                <i class="plus square outline icon"></i>
                Create
            </button>
        </div>
    </div>
    <div class="flex flex-column">
        <div class="mr-1 font-bold border-bottom-1">
            BBox Tracking
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
        </div>
    </div>
</div>
