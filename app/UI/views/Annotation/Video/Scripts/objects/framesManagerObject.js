class FramesManager {
    constructor() {
        this.frames = {};
        this.totalFrames = 0;
        this.frameNumber = -1;
        this.captureVideo = null;
    }

    setConfig(config) {
        this.config = config;
        this.video = config.video;
        this.canvas = config.canvas;
        this.ctx = config.ctx;
    }

    addFrame(frameNumber, frameImage) {
        this.frames[frameNumber] = frameImage;
    }

    async getFrameImage(frameNumber) {
        let frameImage = this.frames[frameNumber];
        if (typeof frameImage === "undefined") {
            frameImage = await this.getFrameFromVideo();
            this.addFrame(frameNumber, frameImage);
        }
        return frameImage;
    }

    async getFrameFromVideo() {
        if (this.supportsVideoFrameAPI()) {
            return await this.captureWithVideoFrameAPI();
        }
        return await this.captureWithCanvasFallback();
    }

    supportsVideoFrameAPI() {
        return 'VideoFrame' in window;
    }

    async captureWithVideoFrameAPI() {
        try {
            const videoFrame = new VideoFrame(this.video);
            const canvas = new OffscreenCanvas(videoFrame.displayWidth, videoFrame.displayHeight);
            const ctx = canvas.getContext('2d');

            ctx.drawImage(videoFrame, 0, 0);
            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

            videoFrame.close();
            return imageData;
        } catch (error) {
            return await this.captureWithCanvasFallback();
        }
    }

    async captureWithCanvasFallback() {
        return await this.captureFrameFromSeparateVideo();
    }

    async initializeCaptureVideo() {
        if (this.captureVideo) {
            this.captureVideo.remove();
        }

        this.captureVideo = document.createElement('video');
        this.captureVideo.crossOrigin = "anonymous";
        this.captureVideo.preload = "metadata";
        this.captureVideo.muted = true;
        this.captureVideo.style.display = 'none';
        this.captureVideo.src = this.video.src || this.video.currentSrc;

        document.body.appendChild(this.captureVideo);

        return new Promise((resolve) => {
            const onReady = () => resolve();

            if (this.captureVideo.readyState >= 1) {
                onReady();
            } else {
                this.captureVideo.addEventListener('loadedmetadata', onReady, { once: true });
                setTimeout(() => {
                    if (this.captureVideo.readyState === 0) {
                        this.captureVideo.load();
                    }
                }, 100);
            }
        });
    }

    async captureFrameFromSeparateVideo() {
        try {
            if (!this.captureVideo || this.captureVideo.readyState < 1) {
                await this.initializeCaptureVideo();
            }

            const targetTime = this.video.currentTime;

            if (!this.captureVideo.duration || targetTime > this.captureVideo.duration) {
                throw new Error("Invalid video duration or target time");
            }

            return await this.performSeekAndCapture(targetTime);
        } catch (error) {
            return this.captureFromMainVideo();
        }
    }

    async performSeekAndCapture(targetTime) {
        return new Promise((resolve, reject) => {
            const timeout = setTimeout(() => {
                reject(new Error("Seek timeout"));
            }, 3000);

            const onSeeked = () => {
                clearTimeout(timeout);
                setTimeout(() => {
                    try {
                        this.ctx.drawImage(this.captureVideo, 0, 0, this.canvas.width, this.canvas.height);
                        const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                        resolve(imageData);
                    } catch (error) {
                        reject(error);
                    }
                }, 50);
            };

            const currentTime = this.captureVideo.currentTime;
            const timeDiff = Math.abs(currentTime - targetTime);

            if (timeDiff < 0.05) {
                clearTimeout(timeout);
                setTimeout(() => {
                    try {
                        this.ctx.drawImage(this.captureVideo, 0, 0, this.canvas.width, this.canvas.height);
                        const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                        resolve(imageData);
                    } catch (error) {
                        reject(error);
                    }
                }, 50);
                return;
            }

            this.captureVideo.addEventListener('seeked', onSeeked, { once: true });
            this.captureVideo.addEventListener('error', () => {
                clearTimeout(timeout);
                reject(new Error("Video error"));
            }, { once: true });

            this.captureVideo.currentTime = targetTime;
        });
    }

    captureFromMainVideo() {
        this.video.crossOrigin = "anonymous";
        this.ctx.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);
        return this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
    }

    // Cleanup method
    destroy() {
        if (this.captureVideo) {
            this.captureVideo.remove();
            this.captureVideo = null;
        }
        this.frames = {};
    }
}
