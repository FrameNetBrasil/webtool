<div x-data="videoComponent()" class="video-player-container">
    <div class="video-wrapper"
         style="position:relative; width:852px;height:480px"
    >
        <video :id="idVideo"
               preload="metadata"
               crossorigin="anonymous"
               @loadstart="log('Load start')"
               @loadedmetadata="onLoadedMetadata()"
               @loadeddata="log('Data loaded')"
               @video-seek-frame.document="onVideoSeekFrame"
               @video-seek-time.document="onVideoSeekTime"
               @tracking-start.document="onTrackingStart"
               @tracking-stop.document="onTrackingStop"
               @play-at-time.document="onPlayAtTime"
               @play-range.document="onPlayRange"
               @canplay="log('Can play')"
               @canplaythrough="log('Can play through')"
               @durationchange="onDurationChange()"
               @timeupdate="onTimeUpdate()"
               @play="onPlay()"
               @pause="onPause()"
               @seeking="onSeeking()"
               @seeked="onSeeked()"
               @progress="updateBuffer()"
               @click="togglePlay()"
               @tracking-mode-toggle.document="onToggleTrackingMode()"
               style="width:852px;height:480px"
        >
            <source src="{!! config('webtool.mediaURL') . "/" . $video->currentURL !!}?t={!! time() !!}"
                    type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <video id="fallbackVideo" style="display:none"></video>
        <!-- Progress bar -->
        <div class="progress-container" @click="seekToPosition($event)">
            <div class="buffer-bar" :style="'width: ' + bufferProgress + '%'"></div>
            <div class="progress-bar" :style="'width: ' + playProgress + '%'"></div>
        </div>

        <div x-show="isSeekingInProgress"
             class="seeking-indicator spinning"
             x-text="'Seeking to frame ' + seekingToFrame + '...'">
        </div>

        <canvas
            id="canvas"
            width=852
            height=480
            style="position: absolute; top: 0; left: 0; background-color: transparent; z-index: 1;"
        ></canvas>

        @include("Annotation.Video.Panes.bbox")
    </div>
    <div
        class="control-bar d-flex justify-between"
    >
        <div style="width:128px;text-align:left;">
            <div class="ui label">
                <span x-text="frame.current"></span> [<span
                    x-text="formatTime(time.current)"></span>]
            </div>
        </div>

        <div
            class="playback-tracking-mode"
        >
{{--            <div class="ui label">--}}
{{--                <span x-text="'Tracking ' + (trackingMode ? 'on' : 'off')"></span>--}}
{{--            </div>--}}
        </div>
        <div id="videoNavigation" class="ui small icon buttons">
            <button
                class="ui button nav"
                :class="(isPlaying || trackingMode) && 'disabled'"
                @click="gotoStart()"
            ><i class="fast backward icon"></i>
            </button>
            <button
                class="ui button nav"
                :class="(isPlaying || trackingMode) && 'disabled'"
                @click="gotoPrevious10Second()"
            ><i class="backward icon"></i>
            </button>
            <button
                class="ui button nav"
                :class="isPlaying && 'disabled'"
                @click="gotoPreviousFrame()"
            ><i class="step backward icon"></i>
            </button>
            <button
                class="ui button toggle"
                :class="trackingMode && 'disabled'"
                @click="togglePlay()"
            ><i :class="isPlaying ? 'pause icon' : 'play icon'"></i>
            </button>
            <button
                class="ui button nav"
                :class="isPlaying && 'disabled'"
                @click="gotoNextFrame()"
            ><i class="step forward icon"></i>
            </button>
            <button
                class="ui button nav"
                :class="(isPlaying || trackingMode) && 'disabled'"
                @click="gotoNext10Second()"
            ><i class="forward icon"></i>
            </button>
            <button
                class="ui button nav"
                :class="(isPlaying || trackingMode) && 'disabled'"
                @click="gotoEnd()"
            ><i class="fast forward icon"></i>
            </button>
        </div>

        <!-- Playback Rate Selector -->
        <div
            class="ui compact dropdown playback-rate-selector"
            x-init="$($el).dropdown();"
        >
            <div class="text" x-text="currentPlaybackRate === 1 ? 'Normal' : currentPlaybackRate + 'x'"></div>
            <i class="dropdown icon"></i>
            <div class="menu">
                <template x-for="rate in playbackRates" :key="rate">
                    <div class="item"
                         :class="rate === currentPlaybackRate && 'active'"
                         @click="setPlaybackRate(rate)"
                         x-text="rate === 1 ? 'Normal' : rate + 'x'">
                    </div>
                </template>
            </div>
        </div>

        <div style="width:128px;text-align:right;">
            <div class="ui label">
                <span x-text="frame.last"></span> [<span
                    x-text="formatTime(duration)"></span>]
            </div>
        </div>
    </div>

</div>
