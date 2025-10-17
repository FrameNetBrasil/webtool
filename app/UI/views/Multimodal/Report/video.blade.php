<script type="text/javascript">
    document.addEventListener('alpine:init', () => {
        window.doStore = Alpine.store('doStore', {
            idVideoJs: 'videoContainer',
            idVideo: 'videoContainer_html5_api',
            fps: 25, // frames por segundo
            timeInterval: 1 / 25, // intervalo entre frames - 0.04s = 40ms
            originalDimensions: {
                width: 852,
                height: 480
            },
            timeCount: 0,
            currentTime : 0,
            timeFormated: (timeSeconds) => {
                let minute = Math.trunc(timeSeconds / 60);
                let seconds = Math.trunc(timeSeconds - (minute * 60));
                return minute + ':' + seconds;
            },
        })
    });
    $(function () {

        let player = videojs("videoContainer", {
            height: parseInt(Alpine.store('doStore').originalDimensions.height / 2),
            width: parseInt(Alpine.store('doStore').originalDimensions.width / 2),
            controls: true,
            autoplay: false,
            preload: "auto",
            playbackRates: [0.2, 0.5, 0.8, 1, 2],
            bigPlayButton: false,
            inactivityTimeout: 0,
            children: {
                controlBar: {
                    playToggle: true,
                    volumePanel: false,
                    remainingTimeDisplay: false,
                    fullscreenToggle: false,
                    pictureInPictureToggle: false,
                },
                mediaLoader: true,
                loadingSpinner: true,
            },
            userActions: {
                doubleClick: false
            }
        });
        player.crossOrigin('anonymous')

        // player.player_.handleTechClick_ = function (event) {
        //     console.log('video clicking')
        //     let state = Alpine.store('doStore').currentVideoState;
        //     if (state === 'paused') {
        //         player.play();
        //     }
        //     if (state === 'playing') {
        //         player.pause();
        //     }
        // };

        //<span class="vjs-icon-placeholder" aria-hidden="true"></span>
        //<span class="vjs-control-text" aria-live="polite">Play</span>

        // button frame forward
        let btnForward = player.controlBar.addChild('button', {}, 0);
        let btnForwardDom = btnForward.el();
        btnForwardDom.innerHTML = '<span class="vjs-icon-placeholder" id="btnForward" aria-hidden="true" title="Next frame"><i class="video-material">skip_next</i></span>';
        btnForwardDom.onclick = function () {
            console.log('click forward');
            let state = Alpine.store('doStore').currentVideoState;
            if (state === 'paused') {
                let currentTime = player.currentTime();
                let newTime = currentTime + annotation.video.timeInterval;
                //console.log('newTime', newTime);
                player.currentTime(newTime);
            }
        };
        // button frame backward
        let btnBackward = player.controlBar.addChild('button', {}, 0);
        let btnBackwardDom = btnBackward.el();
        btnBackwardDom.innerHTML = '<span class="vjs-icon-placeholder"  id="btnBackward" aria-hidden="true" title="Previous frame"><i class="video-material">skip_previous</i></span>';
        btnBackwardDom.onclick = function () {
            console.log('click backward');
            let state = Alpine.store('doStore').currentVideoState;
            if (state === 'paused') {
                let currentTime = player.currentTime();
                if (Alpine.store('doStore').frameCount > 1) {
                    let newTime = currentTime - annotation.video.timeInterval;
                    //console.log('newTime', newTime);
                    player.currentTime(newTime);
                }
            }
        };

        player.ready(function () {
            // Alpine.store('doStore').config();
            player.on('durationchange', () => {
            //     let duration = player.duration();
            //     Alpine.store('doStore').timeDuration = parseInt(duration);
            //     let lastFrame = annotation.video.frameFromTime(duration);
            //     Alpine.store('doStore').frameDuration = lastFrame;
            //     annotation.video.framesRange.last = lastFrame;
            //     //Alpine.store('doStore').updateObjectList();
            //     Alpine.store('doStore').loadLayerList();
            })
            player.on('timeupdate', () => {
                let currentTime = player.currentTime();
                Alpine.store('doStore').timeCount = Math.floor(currentTime * 1000) /1000;
                // // console.error('timeupdate currentTime', currentTime);
                // let currentFrame = annotation.video.frameFromTime(currentTime);
                // // console.log("timeupdate currentFrame ", currentFrame);
                // //currentTime = annotation.video.timeFromFrame(currentFrame);
                // //console.log('time update', currentTime);
                // Alpine.store('doStore').timeCount = Math.floor(currentTime * 1000) /1000;
                // // console.log("timeupdate timecount ", Alpine.store('doStore').timeCount);
                // Alpine.store('doStore').updateCurrentFrame(currentFrame);
                // annotation.timeline.setTime(Math.trunc(currentTime * 1000));
                // if (annotation.video.playingRange) {
                //     if (currentFrame > annotation.video.playingRange.endFrame) {
                //         annotation.video.player.pause();
                //         annotation.video.playingRange = null;
                //     }
                // }
            })
            // player.on('play', () => {
            //     let state = Alpine.store('doStore').currentVideoState;
            //     if (state === 'paused') {
            //         Alpine.store('doStore').currentVideoState = 'playing';
            //         annotation.timeline.onPlayClick();
            //         $btn = document.querySelector("#btnBackward");
            //         if ($btn) {
            //             $btn.style.color = "grey";
            //             $btn.style.cursor = "default";
            //         }
            //         $btn = document.querySelector("#btnForward");
            //         if ($btn) {
            //             $btn.style.color = "grey";
            //             $btn.style.cursor = "default";
            //         }
            //     }
            // })
            // player.on('pause', () => {
            //     //player.currentTime(Alpine.store('doStore').timeCount);
            //     let currentTime = player.currentTime();
            //     console.log('currentTime', currentTime);
            //     Alpine.store('doStore').currentVideoState = 'paused';
            //     annotation.timeline.onPauseClick();
            //     $btn = document.querySelector("#btnBackward");
            //     if ($btn) {
            //         $btn.style.color = "white";
            //         $btn.style.cursor = "pointer";
            //     }
            //     $btn = document.querySelector("#btnForward");
            //     if ($btn) {
            //         $btn.style.color = "white";
            //         $btn.style.cursor = "pointer";
            //     }
            // })
        });


    })
</script>
<div
    style="position:relative; width:415px;height:245px"
>
    <video-js
        id="videoContainer"
        class="video-js"
                src="https://dynamic.frame.net.br/afa00f72fb6fe767d051f2dff2633ee3e67eecdd.mp4"
{{--        src="https://dynamic.frame.net.br/{{$video->sha1Name}}.mp4"--}}
    >
    </video-js>
    <div x-data class="info flex flex-row justify-content-between">
        <div style="width:200px; text-align:left">
            <div class="ui label">
                <span x-text="$store.doStore.timeFormated($store.doStore.timeCount)"></span>
            </div>
        </div>
    </div>

</div>
