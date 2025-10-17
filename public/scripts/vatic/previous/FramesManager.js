"use strict";

class FramesManager {
    constructor() {
        // this.frames = {
        //     totalFrames: () => {
        //         return 0;
        //     }
        // };
        // this.db = {};
        this.frames = {};
        this.onReset = [];
        this.canvas = document.createElement('canvas');
        //this.canvas = document.querySelector('#canvas');
        this.ctx = this.canvas.getContext('2d', {willReadFrequently: true});
        this.dimensionsInitialized = false;
        this.totalFrames = 0;
        this.processedFrames = 0;
        this.lastApproxFrame = -1;
        this.lastProgressFrame = -1;
        this.frameNumber = -1;
        this.interval = 0;
    }

    /*
    onBrowserAnimationFrame() {
        if (this.dimensionsInitialized && this.video.ended) {
            if (this.processedFrames === this.totalFrames) {
                this.videoEnded();
            }
            return;
        }

        compatibility.requestAnimationFrame(this.onBrowserAnimationFrame);

        if (this.video.readyState !== this.video.HAVE_CURRENT_DATA &&
            this.video.readyState !== this.video.HAVE_FUTURE_DATA &&
            this.video.readyState !== this.video.HAVE_ENOUGH_DATA) {
            return;
        }

        let currentApproxFrame = Math.round(this.video.currentTime * this.config.fps);
        if (currentApproxFrame !== this.lastApproxFrame) {
            this.lastApproxFrame = currentApproxFrame;
            this.frameNumber = this.totalFrames;
            this.totalFrames++;

            if (!this.dimensionsInitialized) {
                this.dimensionsInitialized = true;
                this.canvas.width = this.video.videoWidth;
                this.canvas.height = this.video.videoHeight;
            }

            this.ctx.drawImage(this.video, 0, 0);
            this.canvas.toBlob(
                (blob) => {
                    this.processedFrames++;

                    if (this.frameNumber > this.lastProgressFrame) {
                        this.lastProgressFrame = frameNumber;
                        progress(this.video.currentTime / this.video.duration, this.processedFrames, blob);
                    }

                    if (this.video.ended && this.this.processedFrames === totalFrames) {
                        this.videoEnded();
                    }
                },
                this.config.imageMimeType
            );
        }
    }

    progress(percentage, framesSoFar, lastFrameBlob) {
        blobToImage(lastFrameBlob).then((img) => {
            if (!this.dimensionsInitialized) {
                this.dimensionsInitialized = true;
                //initializeCanvasDimensions(img);
            }
            this.ctx.drawImage(img, 0, 0);

            //videoDimensionsElement.innerHTML = 'Video dimensions determined: ' + img.width + 'x' + img.height;
            //extractionProgressElement.innerHTML = (percentage * 100).toFixed(2) + ' % completed. ' + framesSoFar + ' frames extracted.';
        })
    }

    videoEnded() {
        if (this.video.src !== '') {
            URL.revokeObjectURL(this.video.src);
            this.video.src = '';

            resolve({
                totalFrames: () => {
                    return totalFrames;
                },
                getFrame: (frameNumber) => {
                    //return db.getAttachment(frameNumber.toString(), attachmentName);
                }
            });
        }
    }
*/
    setConfig(config) {
        this.config = config;
        this.video = document.getElementById(config.idVideoDOMElement);
        this.dimensionsInitialized = true;
        this.canvas.width = annotation.video.originalDimensions.width;
        this.canvas.height = annotation.video.originalDimensions.height;
        this.canvas.style.position = 'absolute';
        this.canvas.style.top = '0px';
        this.canvas.style.left = '0px';
        this.canvas.style.backgroundColor = "transparent";
        this.config.imageMimeType = "image/png";
    }

    async getFrameFromVideo() {
        // considera que o frame de video está sendo exibido no canvas
        this.video.crossOrigin = "*";
        this.ctx.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);
        return new Promise((resolve, reject) => {
            this.canvas.toBlob(
                (blob) => {
                    resolve(blob);
                },
                this.config.imageMimeType
            );
        });
    }

    addFrame(frameNumber, frameImage) {
        this.frames[frameNumber] = frameImage;
    }

    async getFrame(frameNumber) {
        let frameImage = this.frames[frameNumber];
        if (typeof frameImage === 'undefined') {
            let frameImage = await this.getFrameFromVideo();
            this.addFrame(frameNumber, frameImage);
            return frameImage;
        }
        return frameImage;
    }

    /*
        set(frames) {
            this.frames = frames;
            for (let i = 0; i < this.onReset.length; i++) {
                this.onReset[i]();
            }
        }
        */
}

