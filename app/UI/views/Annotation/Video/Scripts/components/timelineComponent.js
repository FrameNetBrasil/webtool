function timelineComponent(config) {
    return {
        frameInput: 0,
        config: {},
        videoFrame: 0,

        init() {
            this.config = config;
            console.log("Timeline config:", config); // Debug config
            this.generateRuler();
            this.setupScrollSync();

            const timelineContent = document.getElementById("timeline-content");
            const timelineInfo = document.getElementById("timeline-info");
            const rulerContent = document.getElementById("ruler-content");
            const labelsColumn = document.getElementById("labels-column");

            if (timelineContent && timelineInfo) {
                timelineContent.addEventListener("scroll", function() {
                    const scrollLeft = this.scrollLeft;
                    const scrollTop = this.scrollTop;
                    const viewportWidth = this.clientWidth;

                    // Frame calculation (1px = 1 frame, starting from 0)
                    const frameStart = Math.floor(scrollLeft);
                    const frameEnd = Math.floor(scrollLeft + viewportWidth);

                    // Update frame info
                    timelineInfo.textContent = `Viewing frames: ${frameStart.toLocaleString()} - ${frameEnd.toLocaleString()}`;

                    // Sync ruler
                    if (rulerContent) {
                        rulerContent.style.transform = `translateX(-${scrollLeft}px)`;
                    }

                    // Sync labels
                    if (labelsColumn) {
                        labelsColumn.scrollTop = scrollTop;
                    }
                });

                // Labels scroll back to timeline
                if (labelsColumn) {
                    labelsColumn.addEventListener("scroll", function() {
                        timelineContent.scrollTop = this.scrollTop;
                    });
                }
            }

            document.addEventListener("video-update-state", (e) => {
                this.videoFrame = e.detail.frame.current;
            });
        },

        onSeekObject(e) {
            // console.log("onSeekObject", e);
            this.frameInput = e.detail.frameNumber;
            this.scrollToFrame();
        },

        generateRuler: function() {
            const rulerContent = document.getElementById("ruler-content");
            rulerContent.innerHTML = "";

            // Major ticks every 1000 frames
            // for (let frame = config.minFrame; frame <= config.maxFrame; frame += 1000) {
            //     const tick = document.createElement('div');
            //     tick.className = 'ruler-tick major';
            //     tick.style.left = frame + 'px';
            //     tick.textContent = frame.toLocaleString();
            //     rulerContent.appendChild(tick);
            // }
            //
            // Minor ticks every 100 frames
            for (let frame = 100; frame <= config.maxFrame; frame += 100) {
                const tick = document.createElement("div");
                if (frame % 1000 === 0) {
                    tick.className = "tick major";
                } else {
                    tick.className = "tick";
                }
                tick.style.left = frame + "px";
                tick.textContent = frame.toLocaleString();
                rulerContent.appendChild(tick);
            }
        },

        setupScrollSync: function() {
            const timelineContent = document.getElementById("timeline-content");
            const labelsColumn = document.getElementById("labels-column");
            const rulerContent = document.getElementById("ruler-content");
            const timelineInfo = document.getElementById("timeline-info");
            const currentFrame = document.getElementById("current-frame");

            // console.log("Setting up scroll sync with elements:", {
            //     timelineContent: !!timelineContent,
            //     labelsColumn: !!labelsColumn,
            //     rulerContent: !!rulerContent,
            //     timelineInfo: !!timelineInfo,
            //     currentFrame: !!currentFrame
            // });

            if (!timelineContent || !timelineInfo) {
                console.error("Critical elements not found for scroll sync!");
                return;
            }

            // Function to update frame info
            function updateFrameInfo(scrollLeft, viewportWidth) {
                const frameStart = Math.floor(scrollLeft / config.frameToPixel) + config.minFrame;
                const frameEnd = Math.floor((scrollLeft + viewportWidth) / config.frameToPixel) + config.minFrame;

                // console.log("Updating frame info:", {
                //     scrollLeft,
                //     viewportWidth,
                //     frameStart,
                //     frameEnd
                // });

                if (timelineInfo) {
                    timelineInfo.textContent = `Viewing frames: ${frameStart.toLocaleString()} - ${frameEnd.toLocaleString()}`;
                }

                if (currentFrame) {
                    currentFrame.textContent = `Frame: ${frameStart.toLocaleString()}`;
                }
            }

            // Main timeline scroll event
            timelineContent.addEventListener("scroll", function(event) {
                // console.log("Scroll event fired!", { scrollLeft: this.scrollLeft, scrollTop: this.scrollTop });

                const scrollLeft = this.scrollLeft;
                const scrollTop = this.scrollTop;
                const viewportWidth = this.clientWidth;

                // Sync ruler horizontally
                if (rulerContent) {
                    rulerContent.style.transform = `translateX(-${scrollLeft}px)`;
                }

                // Sync labels vertically
                if (labelsColumn) {
                    labelsColumn.scrollTop = scrollTop;
                }

                // Update frame info
                updateFrameInfo(scrollLeft, viewportWidth);
            });

            // Labels scroll back to timeline
            if (labelsColumn) {
                labelsColumn.addEventListener("scroll", function() {
                    timelineContent.scrollTop = this.scrollTop;
                });
            }

            // Initial update
            const initialScrollLeft = timelineContent.scrollLeft;
            const initialViewportWidth = timelineContent.clientWidth;
            updateFrameInfo(initialScrollLeft, initialViewportWidth);

            // console.log("Scroll sync setup complete");
        },

        // Navigation functions
        scrollToFrame: function() {
            const frameNumber = this.frameInput;
            const timelineContent = document.getElementById("timeline-content");

            const framePosition = (frameNumber - config.minFrame) * config.frameToPixel;
            const viewportWidth = timelineContent.clientWidth;
            const centerOffset = viewportWidth / 2;

            let scrollPosition = framePosition - centerOffset;
            scrollPosition = Math.max(0, scrollPosition);

            const maxScroll = timelineContent.scrollWidth - timelineContent.clientWidth;
            scrollPosition = Math.min(scrollPosition, maxScroll);

            timelineContent.scrollTo({
                left: scrollPosition,
                behavior: "smooth"
            });
        },

        scrollToStart: function() {
            document.getElementById("timeline-content").scrollTo({
                left: 0,
                behavior: "smooth"
            });
        },

        scrollToEnd: function() {
            const timelineContent = document.getElementById("timeline-content");
            timelineContent.scrollTo({
                left: timelineContent.scrollWidth - timelineContent.clientWidth,
                behavior: "smooth"
            });
        },

        scrollToVideoFrame: function() {
            this.frameInput = this.videoFrame;
            this.scrollToFrame();
        },

        onClickObject: async function(idObject) {
            htmx.ajax("GET","/annotation/video/object",{
                target:"#formsPane",
                swap:'innerHTML',
                values:{
                    idObject,
                    annotationType: this.config.annotationType,
                    idDocument: this.config.idDocument
                }
            });
        }


        // selectObject: async function(idDynamicObject) {
        //     let dynamicModeObject = await __api.getObject(idDynamicObject);
        //     document.dispatchEvent(new CustomEvent("object-selected", {
        //         detail: {
        //             dynamicModeObject
        //         }
        //     }));
        // }


        // Object click handler
        // objectClick: function(element) {
        //     const rect = element.getBoundingClientRect();
        //     const timelineContent = document.getElementById("timeline-content");
        //     const timelineRect = timelineContent.getBoundingClientRect();
        //
        //     const relativeLeft = rect.left - timelineRect.left + timelineContent.scrollLeft;
        //     const startFrame = Math.round(relativeLeft / config.frameToPixel) + config.minFrame;
        //     const width = rect.width;
        //     const duration = Math.round(width / config.frameToPixel);
        //     const endFrame = startFrame + duration;
        //
        //     console.log("Object clicked:", {
        //         element: element.textContent,
        //         startFrame: startFrame,
        //         endFrame: endFrame,
        //         duration: duration
        //     });
        //
        //     document.getElementById("timeline-info").textContent =
        //         `Clicked: ${element.textContent} (${startFrame}-${endFrame})`;
        // }
    };
}
