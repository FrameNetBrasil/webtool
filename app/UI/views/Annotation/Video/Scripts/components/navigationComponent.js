function navigationComponent() {
    return {
        fps: 25,
        timeInterval: 1 / 25,
        frame: {
            current: 1,
            last: 0
        },
        time: {
            current: "",
            duration: ""
        },
        // framesRange: {
        //     first: 1,
        //     last: 1
        // },
        // playingRange: null,
        isPlaying: false,
        isTracking: false,

        init() {
            this.time.current = "0:00";

            // document.addEventListener("action-toggle", (e) => {
            //     this.toggle();
            // });

//             document.addEventListener("video-update-state", (e) => {
//                 this.frame.current = e.detail.frame.current;
//                 this.time.current = this.timeFormated(e.detail.time.current);
//                 this.isPlaying = e.detail.isPlaying;
//             });
//
//             document.addEventListener("video-update-duration", (e) => {
//                 this.time.duration = this.timeFormated(e.detail.duration);
//                 this.frame.last = e.detail.lastFrame;
// //                let lastFrame = this.frameFromTime(e.detail.duration);
//                 // console.log("lastFrame", lastFrame);
//                 // this.framesRange.last = lastFrame;
//                 // this.frame.last = lastFrame;
//             });

            // document.addEventListener("update-current-time", (e) => {
            //     let frame = this.frameFromTime(e.detail.currentTime);
            //     this.frame.current = frame;
            //     this.time.current = this.timeFormated(this.frame.current);
            //     document.dispatchEvent(new CustomEvent("update-current-frame", {
            //         detail: {
            //             frame
            //         }
            //     }));
            // });

        },

        onVideoUpdateState(e) {
            this.frame.current = e.detail.frame.current;
            this.time.current = this.timeFormated(e.detail.time.current);
            this.isPlaying = e.detail.isPlaying;
        },

        onVideoUpdateDuration(e) {
            this.time.duration = this.timeFormated(e.detail.duration);
            this.frame.last = e.detail.lastFrame;
        },

        onTrackingStart() {
            console.log("onTrackingStart");
            this.isTracking = true;
        },

        onTrackingStop() {
            console.log("onTrackingStop");
            this.isTracking = false;
        },

        timeFormated: (timeSeconds) => {
            let minute = Math.trunc(timeSeconds / 60);
            let seconds = Math.trunc(timeSeconds - (minute * 60));
            return minute + ":" + (seconds < 10 ? '0' : '') + seconds;
        },

        // frameFromTime(timeSeconds) {
        //     return Math.floor(parseFloat(timeSeconds.toFixed(3)) * this.fps) + 1;
        // },
        // timeFromFrame(frameNumber) {
        //     return Math.floor(((frameNumber - 1) * this.timeInterval) * 1000) / 1000;
        // },

        gotoStart() {
            this.gotoFrame(0);
        },

        gotoPrevious10Second() {
            this.gotoFrame(this.frame.current - 250);
        },

        gotoPreviousFrame() {
            this.gotoFrame(this.frame.current - 1);
        },

        gotoNextFrame() {
            this.gotoFrame(this.frame.current + 1);
        },

        gotoNext10Second() {
            this.gotoFrame(this.frame.current + 250);
        },

        gotoEnd() {
            this.gotoFrame(this.frame.last);
        },

        gotoFrame(frameNumber) {
            if (frameNumber < 1) {
                frameNumber = 1;
            }
            if (frameNumber > this.frame.last) {
                frameNumber = this.frame.last;
            }
            // console.log("gotoFrame", frameNumber);
            this.frame.current = frameNumber;
            // console.log("gotoFrame", frameNumber,this.frame);
            // this.time.current = this.timeFromFrame(frameNumber);// + 2e-2;
            document.dispatchEvent(new CustomEvent("video-seek-frame", {
                detail: {
                    frameNumber
                }
            }));
            // this.player.currentTime(this.time.current);
        },
        // gotoTime(time) {
        //     let frame = this.frameFromTime(time);
        //     this.gotoFrame(frame);
        // },

        toggle() {
            document.dispatchEvent(new CustomEvent("video-toggle-play"));
            //const icon= document.querySelector("#videoNavigation button.toggle i");
            // this.toggleVideoNavigationButtons(!this.isPlaying);
            // if (!this.isPlaying) {
            //     // document.dispatchEvent(new CustomEvent("action-play"));
            //     this.disableVideoNavigationButtons();
            // } else {
            //     // document.dispatchEvent(new CustomEvent("action-pause"));
            //     this.enableVideoNavigationButtons();
            // }
            // this.isPlaying = !this.isPlaying;
        },


        // toggleVideoNavigationButtons(disabled = true) {
        //     const buttons = document.querySelectorAll("#videoNavigation button.nav");
        //     buttons.forEach(button => {
        //         button.disabled = disabled;
        //
        //         // Optional: Add visual feedback by toggling a CSS class
        //         if (disabled) {
        //             button.classList.add("disabled");
        //         } else {
        //             button.classList.remove("disabled");
        //         }
        //     });
        // },

        // disableVideoNavigationButtons() {
        //     this.toggleVideoNavigationButtons(true);
        // },
        //
        // enableVideoNavigationButtons() {
        //     this.toggleVideoNavigationButtons(false);
        // },



    };
}
