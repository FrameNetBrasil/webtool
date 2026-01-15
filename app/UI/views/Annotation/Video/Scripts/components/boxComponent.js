function boxComponent(idVideoDOMElement) {
    return {

        idVideoDOMElement: "",
        boxesContainer: null,
        canvas: null,
        ctx: null,
        video: null,
        bgColor: "#ffff00",
        fgColor: "#000",
        offsetX: 0,
        offsetY: 0,
        startX: 0,
        startY: 0,
        mouseX: 0,
        mouseY: 0,
        isDown: false,
        box: {
            x: 0,
            y: 0,
            width: 0,
            height: 0
        },
        previousBBox: null,
        bbox: null,
        object: null,
        currentFrame: 0,
        startFrame: 0,
        endFrame: 0,
        tracker: null,
        isTracking: false,
        hasBBox: false,
        dom: null,
        _token: "",
        currentBBox: null,
        bboxes: {},
        interactionInitialized: false,
        annotationType: '',
        baseURL: '',
        idObject: '',
        isDisplayingAllBBoxes: false,

        bgcolors: [
            "#ffff00",
            "#f21f26",
            "#91c879",
            "#5780d4",
            "#cdeb2d",
            "#4a3c44",
            "#69e2da",
            "#012aaf",
            "#f88006",
            "#53e052",
            "#199601",
            "#ff31d5",
            "#bf5e70",
            "#84059a",
            "#999867",
            "#f8b90d"],
        fgcolors: [
            "#000",
            "#FFF",
            "#000",
            "#000",
            "#000",
            "#FFF",
            "#000",
            "#FFF",
            "#000",
            "#000",
            "#000",
            "#000",
            "#000",
            "#FFF",
            "#000",
            "#000"],


        async init() {
            console.log("Box component init");
            this._token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            this.idVideoDOMElement = idVideoDOMElement;
            this.baseURL = "/annotation/video";


            // this.object = object;
            // this.annotationType = object.annotationType;
            // console.log(this.object);

            this.canvas = document.getElementById("canvas");
            if (!this.canvas) {
                console.error("Canvas element with ID 'canvas' not found.");
                return;
            }
            this.ctx = this.canvas.getContext("2d", {
                willReadFrequently: true
            });

            this.video = document.getElementById(this.idVideoDOMElement);
            if (this.video) {
                const rect = this.video.getBoundingClientRect();
                this.offsetX = rect.x;
                this.offsetY = rect.y;
            } else {
                console.warn(`Video element with ID '${this.idVideoDOMElement}' not found. Offset will be based on canvas position.`);
                const canvasRect = this.canvas.getBoundingClientRect();
                this.offsetX = canvasRect.left;
                this.offsetY = canvasRect.top;
            }

            // this.startFrame = this.object.startFrame;
            // this.endFrame = this.object.endFrame;
            // this.currentFrame = this.object.startFrame;

            // Initialize tracker with vatic library ObjectsTracker
            if (typeof ObjectsTracker !== 'undefined') {
                this.tracker = new ObjectsTracker();
                this.tracker.config({
                    canvas: this.canvas,
                    ctx: this.ctx,
                    video: this.video
                });
                console.log("🔧 ObjectsTracker (vatic library) initialized successfully");
            } else {
                console.error("❌ ObjectsTracker class not available");
                this.tracker = null;
            }
            this.isTracking = false;
            // document.dispatchEvent(new CustomEvent("video-seek-frame", {
            //     detail: {
            //         frameNumber: this.object.startFrame
            //     }
            // }));
        },

        async onVideoUpdateState(e) {
            this.clearBBox();
            this.currentFrame = e.detail.frame.current;
            //if ((this.object) && ((this.currentFrame >= this.object.startFrame) && (this.currentFrame <= this.object.endFrame))) {
            if ((this.object) && ((this.currentFrame >= this.object.startFrame))) {
                console.log("onVideoUpdateState current frame", this.currentFrame, this.object.startFrame, this.object.endFrame);
                await this.showBBox();
                await this.tracking();
            }
        },

        async onObjectLoaded(e) {
            console.log("onObjectLoaded", e.detail.object);
            this.object = e.detail.object;
            this.annotationType = this.object.annotationType;
            this.currentFrame = this.object.startFrame;
            this.bboxes = this.object.bboxes;
            let frameNumber = (this.object.frameNumber > 0) ? this.object.frameNumber : this.object.startFrame;
            document.dispatchEvent(new CustomEvent("video-seek-frame", {
                detail: {
                    frameNumber
                }
            }));
            // console.log(this.bboxes);
            // await this.showBBox();
        },

        // async onBBoxToggleTracking() {
        //
        //     this.isTracking = !this.isTracking;
        //     console.log("boxesComponent onBBoxToggleTracking  - now is " + (this.isTracking ? 'true':'false') );
        //     await this.tracking();
        // },

        async onStartTracking() {
            console.log("bbox onStartTracking");
            this.isTracking = true;
            await this.tracking();
        },

        async onStopTracking() {
            console.log("bbox onStopTracking");
            this.isTracking = false;

            // Mark the current bbox as ground truth to preserve it
            // let bbox = this.bboxes[this.currentFrame];
            // if (bbox && !bbox.isGroundTruth) {
            //     console.log("Promoting current bbox to ground truth on stop");
            //     bbox.isGroundTruth = true;
            //
            //     // Update in database
            //     if (bbox.idBoundingBox) {
            //         await ky.post(`${this.baseURL}/updateBBox`, {
            //             json: {
            //                 _token: this._token,
            //                 idBoundingBox: bbox.idBoundingBox,
            //                 bbox: {
            //                     frameNumber: bbox.frameNumber,
            //                     x: bbox.x,
            //                     y: bbox.y,
            //                     width: bbox.width,
            //                     height: bbox.height,
            //                     blocked: bbox.blocked,
            //                     isGroundTruth: true
            //                 }
            //             }
            //         }).json();
            //
            //         // Update local state
            //         this.bboxes[this.currentFrame] = bbox;
            //         this.currentBBox = bbox;
            //         console.log("BBox promoted to ground truth successfully");
            //     }
            // }
        },

        async onBBoxCreated(e) {
            this.bbox = e.detail.bbox;
            let bbox = new BoundingBox(this.currentFrame, this.bbox.x, this.bbox.y, this.bbox.width, this.bbox.height, true, false);
            console.log("bbox created for object", this.object);
            this.onDisableDrawing();
            bbox.idBoundingBox = await ky.post(`${this.baseURL}/createBBox`, {
                json: {
                    _token: this._token,
                    idObject: this.object.idObject,
                    frameNumber: this.currentFrame,
                    bbox//     bbox: bbox
                }
            }).json();
            this.bboxes[this.bbox.frameNumber] = bbox;
            console.log("bbox created id ", bbox.idBoundingBox);
            // Load frame image for tracking if tracker is available
            if (this.tracker) {
                await this.tracker.getFrameImage(this.currentFrame);
            }
            await this.showBBox();
            messenger.notify("success", "New bbox created.");
        },

        async onBBoxChange(bbox) {
            console.log("on bbox change ", bbox);
            await ky.post(`${this.baseURL}/updateBBox`, {
                json: {
                    _token: this._token,
                    idBoundingBox: bbox.idBoundingBox,
                    bbox
                }
            }).json();
            this.currentBBox = bbox;
            this.bboxes[bbox.frameNumber] = bbox;
            document.dispatchEvent(new CustomEvent("bbox-update", {
                detail: {
                    bbox
                }
            }));
        },

        async onBBoxChangeBlocked(e) {
            let bbox = await this.getCurrentBBox();
            bbox.blocked = e.target.classList.contains('checked') ? 1 : 0;
            console.log("on bbox change blocked ", this.currentBBox, bbox.blocked);
            await ky.post(`${this.baseURL}/updateBBox`, {
                json: {
                    _token: this._token,
                    idBoundingBox: bbox.idBoundingBox,
                    bbox
                }
            }).json();
            this.bboxes[bbox.frameNumber] = bbox;
            this.showBBox();
        },

        onEnableDrawing() {
            this.isDown = false;
            // Bind 'this' context for all event listeners
            this.boundHandleMouseDown = this.handleMouseDown.bind(this);
            this.boundHandleMouseUp = this.handleMouseUp.bind(this);
            this.boundHandleMouseOut = this.handleMouseOut.bind(this);
            this.boundHandleMouseMove = this.handleMouseMove.bind(this);

            this.canvas.addEventListener("mousedown", this.boundHandleMouseDown);
            this.canvas.addEventListener("mouseup", this.boundHandleMouseUp);
            this.canvas.addEventListener("mouseout", this.boundHandleMouseOut);
            this.canvas.addEventListener("mousemove", this.boundHandleMouseMove);
            console.log("Drawing event listeners enabled.");
        },

        onDisableDrawing() {
            this.isDown = false;
            // Use the same bound functions to remove listeners
            if (this.boundHandleMouseDown) {
                this.canvas.removeEventListener("mousedown", this.boundHandleMouseDown);
                this.canvas.removeEventListener("mouseup", this.boundHandleMouseUp);
                this.canvas.removeEventListener("mouseout", this.boundHandleMouseOut);
                this.canvas.removeEventListener("mousemove", this.boundHandleMouseMove);
            }
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height); // Clear canvas on disable
            console.log("Drawing event listeners disabled and canvas cleared.");
        },

        async getCurrentBBox() {
            let bbox = await ky.get(`${this.baseURL}/getBBox`, {
                searchParams: {
                    idObject: this.object.idObject,
                    frameNumber: this.currentFrame,
                    isTracking: this.isTracking ? 1 : 0
                }
            }).json();
            const {idBoundingBox, frameNumber, frameTime, x, y, width, height, blocked, isGroundTruth} = bbox;
            this.currentBBox = {idBoundingBox, frameNumber, frameTime, x, y, width, height, blocked, isGroundTruth};
            return bbox;
        },

        displayBBox(bbox) {
            // Initialize interaction handlers only once
            if (!this.interactionInitialized) {
                this.initializeBBoxInteraction();
            }

            this.drawBBox(bbox);
            this.bboxes[this.currentFrame] = bbox;
            console.log("displayBBOx", bbox);
            document.dispatchEvent(new CustomEvent("bbox-drawn", {
                detail: {
                    bbox,
                }
            }));
        },

        createTrackedBBox(trackedBBox, blocked, isGroundTruth = false) {
            // Check if BoundingBox class is available
            if (typeof BoundingBox !== 'undefined') {
                return new BoundingBox(
                    this.currentFrame,
                    trackedBBox.x,
                    trackedBBox.y,
                    trackedBBox.width,
                    trackedBBox.height,
                    isGroundTruth,
                    blocked
                );
            } else {
                // Fallback: create plain object with same structure
                console.warn("⚠️ BoundingBox class not available - creating plain object");
                return {
                    frameNumber: this.currentFrame,
                    x: trackedBBox.x,
                    y: trackedBBox.y,
                    width: trackedBBox.width,
                    height: trackedBBox.height,
                    isGroundTruth: isGroundTruth,
                    blocked: blocked,
                    idBoundingBox: null, // Will be set later
                    visible: true
                };
            }
        },

        async performTracking(previousBBox) {
            console.log("🎯 Performing tracking for frame", this.currentFrame);

            // Check if tracking is available
            if (!this.tracker || typeof BoundingBox === 'undefined') {
                console.warn("⚠️ Tracking not available - returning unchanged bbox");

                // Return the original bbox as fallback
                return {
                    x: previousBBox.x,
                    y: previousBBox.y,
                    width: previousBBox.width,
                    height: previousBBox.height,
                    frameNumber: this.currentFrame
                };
            }

            try {
                // Get current frame image data
                let currentImageData = await this.tracker.framesManager.getFrameImage(this.currentFrame);

                // Initialize optical flow with previous frame
                this.tracker.opticalFlow.reset();
                let previousImageData = await this.tracker.framesManager.getFrameImage(this.currentFrame - 1);
                this.tracker.opticalFlow.init(previousImageData);

                // Track the bounding box
                let bboxes = [{
                    x: previousBBox.x,
                    y: previousBBox.y,
                    width: previousBBox.width,
                    height: previousBBox.height
                }];
                let newBboxes = this.tracker.opticalFlow.track(currentImageData, bboxes);

                console.log("✅ Tracking completed - previous:", bboxes[0], "new:", newBboxes[0]);

                return {
                    x: newBboxes[0].x,
                    y: newBboxes[0].y,
                    width: newBboxes[0].width,
                    height: newBboxes[0].height,
                    frameNumber: this.currentFrame
                };

            } catch (error) {
                console.error("❌ Tracking failed:", error);

                // Fallback: return unchanged bbox
                return {
                    x: previousBBox.x,
                    y: previousBBox.y,
                    width: previousBBox.width,
                    height: previousBBox.height,
                    frameNumber: this.currentFrame
                };
            }
        },


        async updateExistingBBox(bboxId, newBBox) {
            this.bbox = newBBox;
            this.bboxes[this.bbox.frameNumber] = this.bbox;
            await ky.post(`${this.baseURL}/updateBBox`, {
                json: {
                    _token: this._token,
                    idBoundingBox: bboxId,
                    bbox: newBBox
                }
            }).json();
        },

        async createNewBBox(newBBox) {
            newBBox.idBoundingBox = await ky.post(`${this.baseURL}/createBBox`, {
                json: {
                    _token: this._token,
                    idObject: this.object.idObject,
                    frameNumber: this.currentFrame,
                    bbox: newBBox
                }
            }).json();
            this.bboxes[newBBox.frameNumber] = newBBox;
            this.currentBBox = newBBox;
            return newBBox;
        },

        async handleNonGroundTruthBBox(bbox) {
            console.log("Recreating non-ground truth bbox via tracking for frame", this.currentFrame);
            let previousBBox = this.bboxes[this.currentFrame - 1];
            console.log("previousBBox", previousBBox);

            if (previousBBox && this.isTracking) {
                // Only re-track if tracking is enabled
                let trackedBBox = await this.performTracking(previousBBox);
                let newBBox = this.createTrackedBBox(trackedBBox, bbox.blocked, false);
                newBBox.idBoundingBox = bbox.idBoundingBox; // Keep same ID, update position

                await this.updateExistingBBox(bbox.idBoundingBox, newBBox);
                //this.showBBox(); // Refresh to show updated bbox
                this.displayBBox(newBBox);
            } else {
                // No previous bbox to track from OR tracking is disabled - use existing bbox as is
                console.log(this.isTracking ? "No previous bbox for tracking, using existing bbox" : "Tracking disabled, using existing bbox");
                this.displayBBox(bbox);
            }
        },

        async handleMissingBBox() {
            //  if ((this.currentFrame > this.object.startFrame) && (this.isTracking)) {
            if ((this.currentFrame > this.object.startFrame)) {
                let previousBBox = this.bboxes[this.currentFrame - 1];
                if (previousBBox) {
                    console.log("create new bbox via tracking on frame", this.currentFrame, previousBBox);
                    let trackedBBox = await this.performTracking(previousBBox);
                    let newBBox = this.createTrackedBBox(trackedBBox, previousBBox.blocked, false);
                    await this.createNewBBox(newBBox);
                    return newBBox;
                } else if (this.isTracking) {
                    messenger.notify("warning", "There is no previous BBox to tracking");
                    await this.onStopTracking();
                    return null;
                }
            }
            return null;
        },

        async showBBox() {
            let bbox = await this.getCurrentBBox();
            if (!bbox) {
                bbox = await this.handleMissingBBox();
            }
            console.log('===');
            console.log("showBBox", bbox);
            if (bbox) {
                // if (bbox.isGroundTruth && !this.isTracking) {
                //     // Ground truth bbox when NOT tracking - use as is
                //     console.log("Using ground truth bbox for frame", this.currentFrame);
                //     this.displayBBox(bbox);
                // } else if (bbox.isGroundTruth && this.isTracking) {
                //     // Ground truth bbox when tracking is enabled - use it but allow tracking from it
                //     console.log("Ground truth bbox - serving as tracking reference for frame", this.currentFrame);
                //     this.displayBBox(bbox);
                // } else {
                //     // Non-ground truth bbox - recreate via tracking if enabled
                //     await this.handleNonGroundTruthBBox(bbox);
                // }
                this.displayBBox(bbox);
            }
            document.dispatchEvent(new CustomEvent("bbox-drawn", {
                detail: {
                    bbox
                }
            }));
        },

        onBBoxCreate() {
            console.log('====');
            this.clearBBox();
            this.onEnableDrawing();
            console.log("Drawing mode activated!");
        },

        async tracking() {
            if (this.isTracking) {
                await new Promise(r => setTimeout(r, 800));
                const nextFrame = this.currentFrame + 1;
                console.log("tracking....", (this.isTracking ? 'tracking' : 'not tracking'), nextFrame, this.object.startFrame, this.object.endFrame);
                //if ((nextFrame >= this.object.startFrame) && (nextFrame <= this.object.endFrame)) {
                if ((nextFrame >= this.object.startFrame)) {
                    // console.log("goto Frame ", nextFrame);
                    //this.previousBBox = JSON.parse(JSON.stringify(this.bbox));
                    console.log('going to next frame ', nextFrame);
                    this.gotoFrame(nextFrame);
                }
            } else {
                console.log("tracking...: ", (this.isTracking ? 'tracking' : 'not tracking'));
            }
            // else {
            //     console.log('stoping tracking ');
            //     this.onStopTracking();
            // }
        },


        initializeBBoxInteraction() {
            let bbox = $('.bbox');
            if (bbox.length === 0) return false; // No bbox element found

            let containerHeight = $("#boxesContainer").height();
            let containerWidth = $("#boxesContainer").width();
            console.log("container", containerHeight, containerWidth);

            // Ensure the bbox has required child elements
            if (bbox.find('.handle.center-drag').length === 0) {
                let drag = document.createElement("div");
                drag.className = "handle center-drag";
                bbox.append(drag);
            }

            if (bbox.find('.objectId').length === 0) {
                let objectId = document.createElement("div");
                objectId.className = "objectId";
                bbox.append(objectId);
            }

            // Setup resizable
            bbox.resizable({
                handles: "n, e, s, w, ne, nw, se, sw",
                onResize: (e) => {
                },
                onStopResize: (e) => {
                    // bbox.css("display", "none");
                    let d = e.data;
                    if (d.left < 0) {
                        $(d.target).outerWidth($(d.target).outerWidth() + d.left);
                        d.left = 0;
                        $(d.target).css("left", 0);
                    }
                    if (d.top < 0) {
                        $(d.target).outerHeight($(d.target).outerHeight() + d.top);
                        d.top = 0;
                        $(d.target).css("top", 0);
                    }
                    if (d.left + $(d.target).outerWidth() > containerWidth) {
                        $(d.target).outerWidth(containerWidth - d.left);
                    }
                    if (d.top + $(d.target).outerHeight() > containerHeight) {
                        $(d.target).outerHeight(containerHeight - d.top);
                    }
                    let bboxChanged = new BoundingBox(
                        this.currentFrame,
                        Math.round(d.left),
                        Math.round(d.top),
                        Math.round($(d.target).outerWidth()),
                        Math.round($(d.target).outerHeight()),
                        true, // User-dragged bbox is ground truth
                        this.currentBBox?.blocked || false
                    );
                    bboxChanged.idBoundingBox = this.currentBBox?.idBoundingBox;
                    this.onBBoxChange(bboxChanged);
                }
            });

            // Setup draggable
            bbox.draggable({
                handle: '.handle.center-drag',
                onDrag: (e) => {
                    let d = e.data;
                    if (d.left < 0) {
                        d.left = 0;
                    }
                    if (d.top < 0) {
                        d.top = 0;
                    }
                    if (d.left + $(d.target).outerWidth() > containerWidth) {
                        d.left = containerWidth - $(d.target).outerWidth();
                    }
                    if (d.top + $(d.target).outerHeight() > containerHeight) {
                        d.top = containerHeight - $(d.target).outerHeight();
                    }
                },
                onStopDrag: (e) => {
                    let d = e.data;
                    let bboxChanged = new BoundingBox(
                        this.currentFrame,
                        Math.round(d.left),
                        Math.round(d.top),
                        Math.round($(d.target).outerWidth()),
                        Math.round($(d.target).outerHeight()),
                        true, // User-dragged bbox is ground truth
                        this.currentBBox?.blocked || false
                    );
                    bboxChanged.idBoundingBox = this.currentBBox?.idBoundingBox;
                    this.onBBoxChange(bboxChanged);
                }
            });

            this.interactionInitialized = true;
            console.log("BBox interaction initialized once");
            return true;
        },

        clearBBox: function () {
            $(".bbox").css("display", "none");
        },

        gotoFrame(frameNumber) {
            document.dispatchEvent(new CustomEvent("video-seek-frame", {
                detail: {
                    frameNumber
                }
            }));
        },

        drawBBox(bbox) {
            let $dom = $(".bbox");
            // console.log("drawBBox", bbox, $dom, this.bgColor);
            $dom.css("display", "none");
            if (bbox) {
                if (!this.hidden) {
                    $dom.css({
                        position: "absolute",
                        display: "block",
                        width: bbox.width + "px",
                        height: bbox.height + "px",
                        left: bbox.x + "px",
                        top: bbox.y + "px",
                        borderColor: this.bgColor,
                        backgroundColor: "transparent",
                        opacity: 1
                    });

                    $dom.find(".objectId").css({
                        backgroundColor: this.bgColor,
                        color: this.fgColor
                    });
                    $dom.find(".objectId").text(this.object.idObject);

                    if (this.isTracking) {
                        $dom.css({
                            borderStyle: "dotted",
                            borderWidth: "2px"
                        });
                    } else {
                        $dom.css({
                            borderStyle: "solid",
                            borderWidth: "4px"
                        });
                    }
                    this.visible = true;
                    if (bbox.blocked) {
                        $dom.css({
                            borderStyle: "dashed",
                            backgroundColor: "white",
                            opacity: 0.4
                        });
                    }
                    $dom.css("display", "block");
                }
            }
        },

        async onBBoxDisplayAll(e) {
            const $allBBoxesContainer = $(".allBBoxes");

            if (this.isDisplayingAllBBoxes) {
                // Hide and clear all displayed bboxes
                $allBBoxesContainer.empty();
                $allBBoxesContainer.css("display", "none");
                this.isDisplayingAllBBoxes = false;
            } else {
                console.log(e.detail);
                let bboxes = await ky.get(`${this.baseURL}/getAllBBoxes/${e.detail.idDocument}/${e.detail.frame}`).json();
                console.log(bboxes);

                // Clear any existing bboxes before adding new ones
                $allBBoxesContainer.empty();

                // Create DOM elements for each bbox
                for (let i = 0; i < bboxes.length; i++) {
                    let bbox = bboxes[i];

                    // Get colors from the arrays in sequence
                    let bgColor = this.bgcolors[i % this.bgcolors.length];
                    let fgColor = this.fgcolors[i % this.fgcolors.length];

                    // Create bbox container div
                    let $bboxDiv = $('<div class="bbox bbox-display-all"></div>');

                    // Create objectId label div
                    let $objectIdDiv = $('<div class="objectId"></div>');
                    $objectIdDiv.text(bbox.idDynamicObject);

                    // Append label to bbox
                    $bboxDiv.append($objectIdDiv);

                    // Style the bbox container
                    $bboxDiv.css({
                        position: "absolute",
                        display: "block",
                        width: bbox.width + "px",
                        height: bbox.height + "px",
                        left: bbox.x + "px",
                        top: bbox.y + "px",
                        borderColor: bgColor,
                        borderStyle: "solid",
                        borderWidth: "4px",
                        backgroundColor: "transparent",
                        opacity: 1,
                        pointerEvents: "none" // Make non-interactive
                    });

                    // Style the objectId label
                    $objectIdDiv.css({
                        backgroundColor: bgColor,
                        color: fgColor,
                        display: "inline-block", // Prevent stretching
                        position: "relative",
                        zIndex: 100 // Ensure label appears above bbox borders
                    });

                    // Handle blocked state
                    if (bbox.blocked) {
                        $bboxDiv.css({
                            borderStyle: "dashed",
                            backgroundColor: "white",
                            opacity: 0.4
                        });
                    }

                    // Append to the allBBoxes container
                    $allBBoxesContainer.append($bboxDiv);
                }

                // Show the container
                $allBBoxesContainer.css("display", "block");
                this.isDisplayingAllBBoxes = true;
            }

        },


        handleMouseDown(e) {
            e.preventDefault();
            e.stopPropagation();

            this.startX = parseInt(e.clientX - this.offsetX);
            this.startY = parseInt(e.clientY - this.offsetY);
            this.isDown = true;
        },

        handleMouseUp(e) {
            e.preventDefault();
            e.stopPropagation();
            this.isDown = false;

            // Clear the canvas. This is temporary feedback, the final box will be managed elsewhere.
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

            // Check if a valid box was drawn (i.e., not just a click)
            // You might want to consider Math.abs(this.box.width) and Math.abs(this.box.height)
            // if users can drag in any direction and you always want positive dimensions.
            if (this.box.width !== 0 && this.box.height !== 0) {
                console.log("Box Finalized:", this.box);

                // Dispatch the custom event, using 'this.box' directly
                document.dispatchEvent(new CustomEvent("bbox-created", {
                    detail: {
                        bbox: { // Recreate the bbox object with absolute values for consistency
                            x: Math.min(this.startX, this.mouseX), // Take the smaller X for the top-left
                            y: Math.min(this.startY, this.mouseY), // Take the smaller Y for the top-left
                            width: Math.abs(this.mouseX - this.startX), // Absolute width
                            height: Math.abs(this.mouseY - this.startY) // Absolute height
                        }
                    }
                }));
            }
        },

        handleMouseOut(e) {
            e.preventDefault();
            e.stopPropagation();
            this.isDown = false;
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        },

        drawCrosshairs(x, y) {
            this.ctx.strokeStyle = this.bgColor;
            this.ctx.lineWidth = 1;

            this.ctx.beginPath();
            this.ctx.moveTo(x, 0);
            this.ctx.lineTo(x, this.canvas.height);
            this.ctx.stroke();

            this.ctx.beginPath();
            this.ctx.moveTo(0, y);
            this.ctx.lineTo(this.canvas.width, y);
            this.ctx.stroke();
        },

        handleMouseMove(e) {
            e.preventDefault();
            e.stopPropagation();

            this.mouseX = parseInt(e.clientX - this.offsetX);
            this.mouseY = parseInt(e.clientY - this.offsetY);

            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

            this.drawCrosshairs(this.mouseX, this.mouseY);

            if (!this.isDown) {
                return;
            }

            const width = this.mouseX - this.startX;
            const height = this.mouseY - this.startY;

            this.ctx.strokeStyle = this.bgColor;
            this.ctx.strokeRect(this.startX, this.startY, width, height);

            this.box = {
                x: this.startX,
                y: this.startY,
                width: width,
                height: height
            };
        }


    };
}
