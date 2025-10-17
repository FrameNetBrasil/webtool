function formsComponent(idDocument) {
    return {
        idDocument: 0,
        formsPane: null,
        currentFrame: 1,
        isPlaying: false,
        trackingMode: false,
        autoTracking: false,
        bboxDrawn: null,

        init() {
            this.idDocument = idDocument;
            this.formsPane = document.getElementById("formsPane");
        },

        onVideoUpdateState(e) {
            this.currentFrame = e.detail.frame.current;
            this.isPlaying = e.detail.isPlaying;
        },

        onBBoxToggleTracking() {
            this.autoTracking = !this.autoTracking;
            console.log("formsComponent toggle tracking - now is " + (this.autoTracking ? 'true' : 'false'));
            if (this.autoTracking) {
                document.dispatchEvent(new CustomEvent("auto-tracking-start"));
            } else {
                document.dispatchEvent(new CustomEvent("auto-tracking-stop"));
            }
        },

        onBBoxDrawn(e) {
            console.log("formsComponent onBBoxDrawn", e.detail.bbox);
            this.bboxDrawn = e.detail.bbox;
        },

        onBBoxUpdate(e) {
            console.log("formsComponent onBBoxUpdate", e.detail.bbox);
            this.bboxDrawn = e.detail.bbox;
        },

        onCloseObjectPane() {
            window.location.assign(`/annotation/{{$annotationType}}/${this.idDocument}`);
        },

        copyFrameFor(name) {
            console.log(name);
            const input = document.querySelector(`input[name="${name}"]`);
            input.value = this.currentFrame;
        }

    };
}
