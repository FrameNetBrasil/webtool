class FramesManager {
    constructor() {
        this.frames = {};
        this.onReset = [];
        this.canvas = document.createElement("canvas");
        this.ctx = this.canvas.getContext("2d", { willReadFrequently: true });
        this.dimensionsInitialized = false;
        this.totalFrames = 0;
        this.processedFrames = 0;
        this.lastApproxFrame = -1;
        this.lastProgressFrame = -1;
        this.frameNumber = -1;
        this.interval = 0;
    }

    setConfig(config) {
        this.config = config;
        // Support both old (idVideoDOMElement) and new (video) config patterns
        if (config.video) {
            this.video = config.video;
        } else if (config.idVideoDOMElement) {
            this.video = document.getElementById(config.idVideoDOMElement);
        }

        this.dimensionsInitialized = true;
        // Use video dimensions if available, otherwise use canvas dimensions from config
        if (this.video) {
            this.canvas.width = this.video.videoWidth || config.canvas?.width || 640;
            this.canvas.height = this.video.videoHeight || config.canvas?.height || 480;
        } else if (config.canvas) {
            this.canvas.width = config.canvas.width;
            this.canvas.height = config.canvas.height;
        }
        this.canvas.style.position = "absolute";
        this.canvas.style.top = "0px";
        this.canvas.style.left = "0px";
        this.canvas.style.backgroundColor = "transparent";
        this.config.imageMimeType = "image/png";
    }

    async getFrameFromVideo() {
        // considera que o frame de video está sendo exibido no canvas
        this.video.crossOrigin = "*";
        this.ctx.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);
        return this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
        // return new Promise((resolve, reject) => {
        //     this.canvas.toBlob(
        //         (blob) => {
        //             resolve(blob);
        //         },
        //         this.config.imageMimeType
        //     );
        // });
    }

    addFrame(frameNumber, frameImage) {
        this.frames[frameNumber] = frameImage;
    }

    async getFrameImage(frameNumber) {
        let frameImage = this.frames[frameNumber];
        if (typeof frameImage === "undefined") {
            let frameImage = await this.getFrameFromVideo();
            this.addFrame(frameNumber, frameImage);
            return frameImage;
        }
        return frameImage;
    }

}

