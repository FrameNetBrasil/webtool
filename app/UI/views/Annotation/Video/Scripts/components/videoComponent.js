// Video component with modular architecture
// Organized into logical sections for better maintainability
function videoComponent() {
    return {
        // =============================================
        // CONFIGURATION & STATE
        // =============================================

        // Configuration (matching original structure)
        idVideoJs: "videoContainer",
        idVideo: "videoContainer_html5_api",
        fps: 25, // frames per second
        timeInterval: 0.04, // interval between frames - 0.04s = 40ms
        dimensions: {
            width: 852,
            height: 480
        },
        trackingMode: false,
        autoTracking: false,

        // State variables
        player: null,
        frame: {
            current: 1,
            last: 0
        },
        time: {
            current: "",
        },
        isPlaying: false,
        duration: 0,
        durationCheckInterval: null,

        // UI state
        isSeekingInProgress: false,
        seekingToFrame: 0,
        seekFrameInput: 1,
        playProgress: 0,
        bufferProgress: 0,
        readyState: 'HAVE_NOTHING',

        // Range playback
        rangeStart: 1,
        rangeEnd: 100,
        playingRange: null,

        // Playback rate control
        playbackRates: [0.2, 0.5, 0.8, 1, 2],
        currentPlaybackRate: 1,

        // Debug
        logs: [],
        logCounter: 0,

        // =============================================
        // LIFECYCLE & INITIALIZATION
        // =============================================

        init() {
            // Use $nextTick to ensure DOM is ready
            this.$nextTick(() => {
                console.log("videoComponent init");
                this.time.current = "0:00";
                this.player = document.getElementById(this.idVideo);

                if (!this.player) {
                    console.error('Video element not found!');
                    return;
                }

                this.log('Video component initialized');

                // Set video dimensions if needed
                if (this.dimensions.width && this.dimensions.height) {
                    this.player.style.maxWidth = this.dimensions.width + 'px';
                    this.player.style.maxHeight = this.dimensions.height + 'px';
                }

                // Set initial playback rate
                this.setPlaybackRate(this.currentPlaybackRate);

                // Start checking for duration availability
                this.startDurationCheck();

                // Dispatch init event
                document.dispatchEvent(new CustomEvent("disable-drawing"));
            });
        },

        reloadVideo() {
            if (!this.player) {
                console.warn('Player not initialized yet');
                return;
            }

            this.log('Reloading video...');

            // Store current source
            const currentSrc = this.player.currentSrc || this.player.src;

            // Reset state
            this.duration = 0;
            this.frame.current = 1;
            this.frame.last = 0;
            this.time.current = 0;
            this.isPlaying = false;
            this.playProgress = 0;
            this.bufferProgress = 0;

            // Reload the video
            this.player.load();

            // Restart duration checking
            this.startDurationCheck();

            this.log('Video reload initiated');

            // Dispatch reload event
            document.dispatchEvent(new CustomEvent('video-reloaded', {
                detail: { src: currentSrc }
            }));
        },

        destroy() {
            if (this.durationCheckInterval) {
                clearInterval(this.durationCheckInterval);
                this.durationCheckInterval = null;
            }
        },

        // =============================================
        // EVENT HANDLERS
        // =============================================

        async onVideoSeekFrame(e) {
            await this.waitForPlayerReady();
            this.seekToFrame(e.detail.frameNumber);
        },

        async onVideoSeekTime(e) {
            await this.waitForPlayerReady();
            let frame = this.frameFromTime(e.detail.time);
            this.seekToFrame(frame);
        },

        onLoadedMetadata() {
            this.log('Video metadata loaded, seeking should work better');
            this.dimensions.width = this.player.videoWidth || this.dimensions.width;
            this.dimensions.height = this.player.videoHeight || this.dimensions.height;
            this.updateReadyState();

            // Try to get duration, but don't rely on it being available yet
            this.checkAndSetDuration();
        },

        onDurationChange() {
            this.log('Duration change event fired');
            this.checkAndSetDuration();
        },

        onCanPlay() {
            this.log('Can play - checking duration again');
            this.checkAndSetDuration();
        },

        onCanPlayThrough() {
            this.log('Can play through - final duration check');
            this.checkAndSetDuration();
        },

        onTimeUpdate() {
            this.time.current = this.player.currentTime;
            this.frame.current = this.frameFromTime(this.time.current);
            this.updateProgress();
            this.broadcastState();

            // Check range playback
            if (this.playingRange && this.frame.current >= this.playingRange.endFrame) {
                this.player.pause();
                this.playingRange = null;
                this.log('Range playback completed');
            }
        },

        onPlay() {
            this.isPlaying = true;
            this.broadcastState();
            this.log('Video started playing');
        },

        onPause() {
            this.isPlaying = false;
            this.broadcastState();
            this.log('Video paused');
        },

        onSeeking() {
            this.isSeekingInProgress = true;
            this.seekingToFrame = this.frameFromTime(this.player.currentTime);
            this.log(`Seeking to frame: ${this.seekingToFrame}`);
        },

        onSeeked() {
            this.isSeekingInProgress = false;
            this.time.current = this.player.currentTime;
            this.frame.current = this.frameFromTime(this.time.current);
            this.updateProgress();
            this.log(`Seeked to frame: ${this.frame.current} (${this.time.current.toFixed(3)}s)`);
        },

        // onTrackingStart() {
        //     console.log("onTrackingStart");
        //     this.isTracking = true;
        // },
        //
        // onTrackingStop() {
        //     console.log("onTrackingStop");
        //     this.isTracking = false;
        // },

        updateReadyState() {
            const readyStates = {
                0: 'HAVE_NOTHING',
                1: 'HAVE_METADATA',
                2: 'HAVE_CURRENT_DATA',
                3: 'HAVE_FUTURE_DATA',
                4: 'HAVE_ENOUGH_DATA'
            };

            this.readyState = readyStates[this.player.readyState] || 'UNKNOWN';
            this.log(`Ready state: ${this.readyState} (${this.player.readyState})`);
        },

        onToggleTrackingMode() {
            this.trackingMode = !this.trackingMode;
        },

        async onPlayAtTime(e) {
            console.log("onPlayAtTime", e.detail);
            await this.waitForPlayerReady();
            let frame = this.frameFromTime(e.detail.time);
            this.seekToFrame(frame);
            // this.player.pause();
            this.player.play();
        },

        onPlayRange(e) {
            console.log("onPlayRange", e.detail);
        },

        // =============================================
        // CORE VIDEO OPERATIONS
        // =============================================

        broadcastState() {
            document.dispatchEvent(new CustomEvent('video-update-state', {
                detail: {
                    frame: this.frame,
                    time: this.time,
                    isPlaying: this.isPlaying
                }
            }));
        },

        async seekToFrame(frame) {
            if (!this.player) {
                console.warn('Player not initialized yet');
                return;
            }

            const targetFrame = Math.max(1, Math.min(this.frame.last, parseInt(frame)));
            const targetTime = this.timeFromFrame(targetFrame);
            this.log(`Seeking to frame ${targetFrame} (${targetTime.toFixed(3)}s)`);
            this.player.currentTime = targetTime;
        },

        async preciseSeekToFrame(frame) {
            console.log("preciseSeekToFrame last", this.frame.last);
            const targetFrame = Math.max(1, Math.min(this.frame.last, parseInt(frame)));
            this.log(`Precise seek to frame ${targetFrame} - STARTED`);

            try {
                await this.performPreciseSeek(targetFrame);
                this.log(`Precise seek to frame ${targetFrame} - COMPLETED`);
            } catch (error) {
                this.log(`Precise seek failed: ${error.message}`);
            }
        },

        async performPreciseSeek(targetFrame) {
            const targetTime = this.timeFromFrame(targetFrame);

            return new Promise((resolve, reject) => {
                const maxAttempts = 3;
                let attempt = 0;

                const attemptSeek = () => {
                    attempt++;
                    this.log(`  Attempt ${attempt}/${maxAttempts}`);

                    const onSeeked = () => {
                        const actualFrame = this.frameFromTime(this.player.currentTime);
                        const frameDiff = Math.abs(actualFrame - targetFrame);

                        this.log(`  Actual frame: ${actualFrame}, diff: ${frameDiff}`);

                        if (frameDiff < 1) {
                            // Success - force visual update
                            this.forceFrameUpdate();
                            resolve();
                        } else if (attempt < maxAttempts) {
                            // Retry
                            this.player.removeEventListener('seeked', onSeeked);
                            setTimeout(attemptSeek, 100);
                        } else {
                            this.player.removeEventListener('seeked', onSeeked);
                            reject(new Error(`Failed after ${maxAttempts} attempts`));
                        }
                    };

                    this.player.addEventListener('seeked', onSeeked, { once: true });
                    this.player.currentTime = targetTime;
                };

                attemptSeek();
            });
        },

        onProgressClick(e) {
            if (!this.player || !this.duration) {
                return;
            }

            const rect = e.currentTarget.getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width;
            const targetFrame = Math.floor(pos * this.frame.last) + 1;

            this.log(`Click seek to frame ${targetFrame}`);
            this.seekToFrame(targetFrame);
        },

        forceFrameUpdate() {
            // Method 1: Opacity trick
            this.player.style.opacity = '0.99';
            requestAnimationFrame(() => {
                this.player.style.opacity = '';
            });

            // Method 2: Transform trick
            requestAnimationFrame(() => {
                this.player.style.transform = 'translateZ(0) scale(1.0001)';
                requestAnimationFrame(() => {
                    this.player.style.transform = 'translateZ(0)';
                });
            });
        },

        frameFromTime(time) {
            return Math.floor(time / this.timeInterval) + 1;
        },

        timeFromFrame(frame) {
            return (frame - 1) * this.timeInterval + (2/1000);
        },

        waitForDuration() {
            return new Promise((resolve) => {
                if (this.duration && this.duration > 0) {
                    resolve(this.duration);
                } else {
                    const checkDuration = () => {
                        if (this.duration && this.duration > 0) {
                            resolve(this.duration);
                        } else {
                            setTimeout(checkDuration, 50);
                        }
                    };
                    checkDuration();
                }
            });
        },

        waitForPlayerReady() {
            return new Promise((resolve) => {
                const isReady = () => {
                    return this.player &&
                        this.player.readyState >= 1 &&
                        this.duration &&
                        this.duration > 0;
                };

                if (isReady()) {
                    resolve(this.player);
                } else {
                    const checkReady = () => {
                        if (isReady()) {
                            resolve(this.player);
                        } else {
                            setTimeout(checkReady, 50);
                        }
                    };
                    checkReady();
                }
            });
        },

        // =============================================
        // NAVIGATION & CONTROLS
        // =============================================

        gotoStart() {
            this.gotoFrame(1);
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
            this.frame.current = frameNumber;
            this.seekToFrame(frameNumber);
        },

        togglePlay() {
            if (!this.player) {
                console.warn('Player not initialized yet');
                return;
            }

            if (this.isPlaying) {
                this.player.pause();
            } else {
                this.player.play().catch(e => this.log(`Play error: ${e.message}`));
            }
        },

        setCurrentAsRangeStart() {
            this.rangeStart = this.frame.current;
            this.log(`Set range start to frame ${this.rangeStart}`);
        },

        setCurrentAsRangeEnd() {
            this.rangeEnd = this.frame.current;
            this.log(`Set range end to frame ${this.rangeEnd}`);
        },

        // Range playback methods (matching original structure)
        playByRange(startTime, endTime, offset) {
            const playRange = {
                startFrame: this.frameFromTime(startTime - offset),
                endFrame: this.frameFromTime(endTime + offset)
            };
            this.playRange(playRange);
        },

        playByFrameRange(startFrame, endFrame) {
            const playRange = {
                startFrame: parseInt(startFrame),
                endFrame: parseInt(endFrame)
            };
            this.playRange(playRange);
        },

        playRange(range) {
            this.playingRange = range;
            this.seekToFrame(range.startFrame);
            this.log(`Playing range: frames ${range.startFrame} to ${range.endFrame}`);
            setTimeout(() => {
                this.player.play();
            }, 100);
        },

        seekToPosition(e) {
            if (!this.duration || this.duration <= 0) {
                this.log('Cannot seek - duration not available yet');
                return;
            }

            const rect = e.currentTarget.getBoundingClientRect();
            const pos = (e.clientX - rect.left) / rect.width;
            const targetFrame = Math.floor(pos * this.frame.last) + 1;

            this.log(`Click seek to frame ${targetFrame}`);
            this.seekToFrame(targetFrame);
        },

        // =============================================
        // DURATION & TIMING
        // =============================================

        startDurationCheck() {
            this.log('Starting duration check interval');

            // Clear any existing interval
            if (this.durationCheckInterval) {
                clearInterval(this.durationCheckInterval);
            }

            // Check duration every 100ms until we get a valid value
            this.durationCheckInterval = setInterval(() => {
                if (this.checkAndSetDuration()) {
                    // Duration found, stop checking
                    clearInterval(this.durationCheckInterval);
                    this.durationCheckInterval = null;
                    this.log('Duration check interval cleared - duration found');
                }
            }, 100);

            // Safety timeout - stop checking after 10 seconds
            setTimeout(() => {
                if (this.durationCheckInterval) {
                    clearInterval(this.durationCheckInterval);
                    this.durationCheckInterval = null;
                    this.log('Duration check timeout - stopped checking');
                }
            }, 10000);
        },

        checkAndSetDuration() {
            if (!this.player) return false;

            const newDuration = this.player.duration;

            // Check if we have a valid, finite duration that's greater than 0
            if (newDuration && isFinite(newDuration) && newDuration > 0) {
                // Only update if duration has actually changed
                if (Math.abs(this.duration - newDuration) > 0.01) {
                    this.duration = newDuration;
                    this.frame.last = this.frameFromTime(this.duration);
                    this.showDuration();

                    // Update progress bar max values
                    this.updateSeekInputMax();

                    // Dispatch duration update event
                    document.dispatchEvent(new CustomEvent("video-update-duration", {
                        detail: {
                            duration: this.duration,
                            lastFrame: this.frame.last
                        }
                    }));

                    this.log(`✅ Duration updated: ${this.duration.toFixed(3)}s (${this.frame.last} frames)`);
                    return true;
                }

                // Duration exists but hasn't changed
                return true;
            }

            // Log current state for debugging
            this.log(`⏳ Duration not ready: ${newDuration} (readyState: ${this.player.readyState})`);
            return false;
        },

        updateSeekInputMax() {
            // Update the max value for frame input field
            const frameInput = document.querySelector('input[x-model="seekFrameInput"]');
            if (frameInput) {
                frameInput.setAttribute('max', this.frame.last);
            }
        },

        showDuration() {
            if (this.duration && !isNaN(this.duration)) {
                const durationFormatted = this.formatDuration(this.duration);
                const minutes = Math.floor(this.duration / 60);
                const seconds = (this.duration % 60).toFixed(2);

                this.log(`📹 Video Duration: ${durationFormatted} (${this.duration.toFixed(2)}s)`);
                this.log(`📊 Total Frames: ${this.frame.last} frames at ${this.fps}fps`);
                this.log(`⏱️  Time per frame: ${this.timeInterval}s (${(this.timeInterval * 1000).toFixed(1)}ms)`);

                // Dispatch duration info event
                document.dispatchEvent(new CustomEvent("video-duration-info", {
                    detail: {
                        durationSeconds: this.duration,
                        durationFormatted: durationFormatted,
                        totalFrames: this.frame.last,
                        fps: this.fps,
                        timePerFrame: this.timeInterval
                    }
                }));

                return durationFormatted;
            }
            return '00:00:00';
        },

        formatDuration(seconds) {
            if (isNaN(seconds)) return '00:00:00';

            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const secs = Math.floor(seconds % 60);
            const milliseconds = Math.floor((seconds % 1) * 1000);

            if (hours > 0) {
                return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}.${milliseconds.toString().padStart(3, '0')}`;
            } else {
                return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}.${milliseconds.toString().padStart(3, '0')}`;
            }
        },

        updateProgress() {
            if (this.duration && this.duration > 0) {
                this.playProgress = (this.time.current / this.duration) * 100;
            } else {
                this.playProgress = 0;
            }
        },

        timeFormated: (timeSeconds) => {
            let minute = Math.trunc(timeSeconds / 60);
            let seconds = Math.trunc(timeSeconds - (minute * 60));
            return minute + ":" + (seconds < 10 ? '0' : '') + seconds;
        },

        formatTime(seconds) {
            if (isNaN(seconds)) return '00:00';
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        },

        updateBuffer() {
            if (this.player) {
                if (this.player.buffered.length > 0 && this.duration) {
                    const buffered = this.player.buffered.end(this.player.buffered.length - 1);
                    this.bufferProgress = (buffered / this.duration) * 100;
                }
            }
        },

        // =============================================
        // PLAYBACK RATE CONTROL
        // =============================================

        setPlaybackRate(rate) {
            if (!this.player) {
                console.warn('Player not initialized yet');
                return;
            }

            const validRate = this.playbackRates.includes(parseFloat(rate)) ? parseFloat(rate) : 1;
            this.currentPlaybackRate = validRate;
            this.player.playbackRate = validRate;
            this.log(`Playback rate set to ${validRate}x (effective fps: ${(this.fps * validRate).toFixed(1)})`);

            // Dispatch custom event for rate change
            document.dispatchEvent(new CustomEvent('video-playback-rate-changed', {
                detail: {
                    rate: validRate,
                    effectiveFps: this.fps * validRate
                }
            }));
        },

        resetPlaybackRate() {
            this.setPlaybackRate(1);
        },

        increasePlaybackRate() {
            const currentIndex = this.playbackRates.indexOf(this.currentPlaybackRate);
            if (currentIndex < this.playbackRates.length - 1) {
                this.setPlaybackRate(this.playbackRates[currentIndex + 1]);
            }
        },

        decreasePlaybackRate() {
            const currentIndex = this.playbackRates.indexOf(this.currentPlaybackRate);
            if (currentIndex > 0) {
                this.setPlaybackRate(this.playbackRates[currentIndex - 1]);
            }
        },

        // =============================================
        // DEBUG & TESTING
        // =============================================

        async testRandomSeeks() {
            this.log('=== STARTING RANDOM SEEK TEST ===');
            const testFrames = [];

            // Generate random frames
            for (let i = 0; i < 5; i++) {
                testFrames.push(Math.floor(Math.random() * this.frame.last) + 1);
            }

            for (const frame of testFrames) {
                try {
                    await this.performPreciseSeek(frame);
                    await new Promise(resolve => setTimeout(resolve, 500));
                } catch (error) {
                    this.log(`Random seek to frame ${frame} failed: ${error.message}`);
                }
            }

            this.log('=== RANDOM SEEK TEST COMPLETED ===');
        },

        log(message) {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = {
                id: ++this.logCounter,
                message: `${timestamp}: ${message}`
            };

            this.logs.push(logEntry);

            // Keep only last 100 logs
            if (this.logs.length > 100) {
                this.logs.shift();
            }

            console.log(logEntry.message);
        },

        clearLogs() {
            this.logs = [];
            this.log('Logs cleared');
        }
    };
}
