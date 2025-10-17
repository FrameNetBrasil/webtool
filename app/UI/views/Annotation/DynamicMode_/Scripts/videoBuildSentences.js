annotation.video = {
    idVideoJs: 'videoContainer',
    idVideo: 'videoContainer_html5_api',
    fps: 25, // frames por segundo
    timeInterval: 1 / 25, // intervalo entre frames - 0.04s = 40ms
    originalDimensions: {
        width: 640,
        height: 360
    },
    player: null,
    frameFromTime(timeSeconds) {
        return parseInt(timeSeconds * annotation.video.fps) + 1;
    },
    timeFromFrame(frameNumber) {
        return ((frameNumber - 1) * annotation.video.timeInterval);
    },
    framesRange: {
        first: 1,
        last: 1
    },
    playingRange: null,
    gotoFrame(frameNumber) {
        let time = annotation.video.timeFromFrame(frameNumber);
        annotation.video.player.currentTime(time);
    },
    enablePlayPause() {
        $btn = document.querySelector(".vjs-play-control");
        if ($btn) {
            $btn.disabled = false;
            $btn.style.color = "white";
            $btn.style.cursor = "pointer";
        }
    },
    disablePlayPause() {
        $btn = document.querySelector(".vjs-play-control");
        if ($btn) {
            $btn.disabled = true;
            $btn.style.color = "grey";
            $btn.style.cursor = "default";
        }
    },
    enableSkipFrame() {
        $btn = document.querySelector("#btnBackward");
        if ($btn) {
            $btn.style.color = "white";
            $btn.style.cursor = "pointer";
        }
        $btn = document.querySelector("#btnForward");
        if ($btn) {
            $btn.style.color = "white";
            $btn.style.cursor = "pointer";
        }
    },
    disableSkipFrame() {
        $btn = document.querySelector("#btnBackward");
        if ($btn) {
            $btn.style.color = "grey";
            $btn.style.cursor = "default";
        }
        $btn = document.querySelector("#btnForward");
        if ($btn) {
            $btn.style.color = "grey";
            $btn.style.cursor = "default";
        }
    },
    playByRange(startTime, endTime, offset) {
        console.log('startTime',startTime);
        console.log('offset',offset);
        let playRange = {
            startFrame: annotation.video.frameFromTime(startTime - offset),
            endFrame: annotation.video.frameFromTime(endTime + offset)
        };
        annotation.video.playRange(playRange);
    },
    playRange(range) {
        annotation.video.playingRange = range;
        annotation.video.gotoFrame(range.startFrame);
        annotation.video.player.play();
    }
};
