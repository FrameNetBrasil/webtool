function boxComponent(idDocument, scale, bboxes) {
    return {

        boxesContainer: null,
        canvas: null,
        ctx: null,
        bgColor: "#f21f26",
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
        currentBBox: null,
        object: null,
        hasBBox: false,
        dom: null,
        _token: "",
        toggleShow: true,
        realBBoxes: {},
        scaledBBoxes: {},
        interactionInitialized: {},
        annotationType: '',
        baseURL: '',
        idDocument: 0,
        idObject: '',
        currentFrame: 1,
        scale: 1,
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
            this.baseURL = "/annotation/image";

            this.canvas = document.getElementById("canvas");
            if (!this.canvas) {
                console.error("Canvas element with ID 'canvas' not found.");
                return;
            }
            this.ctx = this.canvas.getContext("2d", {
                willReadFrequently: true
            });
            this.idDocument = idDocument;
            this.scale = scale;
            this.currentBBox = null;
            this.realBBoxes = bboxes;
            _.forEach(bboxes, (realBBox) => {
                this.scaledBBoxes[realBBox.idObject] = this.scaleBBox(realBBox);
                this.displayBBox(this.scaledBBoxes[realBBox.idObject]);
            });
        },

        async onObjectLoaded(e) {
            console.log("onObjectLoaded", e.detail.object);
            this.object = e.detail.object;
            this.annotationType = this.object.annotationType;
            this.currentFrame = this.object.startFrame;
            let idBoundingBox = this.object.bbox.idBoundingBox;
            if (!this.scaledBBoxes[idBoundingBox]) {
                this.realBBoxes[idBoundingBox] = this.object.bbox;
                this.scaledBBoxes[idBoundingBox] = this.scaleBBox(this.object.bbox);
            }
            this.clearBBox();
            let scaledBBox = this.scaledBBoxes[this.object.bbox.idBoundingBox];
            this.displayBBox(scaledBBox);
        },

        async onBBoxCreated(e) {
            this.bbox = e.detail.bbox;
            let bbox = new BoundingBox(this.currentFrame, this.bbox.x, this.bbox.y, this.bbox.width, this.bbox.height, true, false);
            // bbox create with screen dimensions
            let realBBox = this.unscaleBBox(bbox);
            console.log("bbox created for document", this.idDocument);
            this.onDisableDrawing();
            let object = await ky.post(`${this.baseURL}/createBBox`, {
                json: {
                    _token: this._token,
                    idDocument: this.idDocument,
                    bbox: realBBox
                }
            }).json();
            window.location.assign(`/annotation/${object.annotationType}/${this.idDocument}/${object.idStaticObject}`);
        },

        async onBBoxChange(scaledBBox) {
            console.log("on bbox change ", scaledBBox);
            // box with screen dimensions
            let realBBox = this.unscaleBBox(scaledBBox);
            await this.onBBoxUpdate(realBBox);
            document.dispatchEvent(new CustomEvent("bbox-update", {
                detail: {
                    scaledBBox
                }
            }));
        },

        async onBBoxUpdate(realBBox) {
            // receive bbox with screen dimensions
            console.log("onBBoxUpdate", realBBox);
            await ky.post(`${this.baseURL}/updateBBox`, {
                json: {
                    _token: this._token,
                    idBoundingBox: realBBox.idBoundingBox,
                    bbox: realBBox
                }
            }).json();
        },

        async onBBoxChangeBlocked(e) {
            let realBBox = await this.getCurrentBBox();
            realBBox.blocked = e.target.classList.contains('checked') ? 1 : 0;
            await this.onBBoxUpdate(realBBox);
            this.displayBBox(this.scaledBBoxes[realBBox.idBoundingBox]);
        },

        onBBoxToggleShow() {
            this.toggleShow = !this.toggleShow;
            console.log(this.toggleShow);
            if (this.toggleShow) {
                $(".bbox").css("display", "block");
            } else {
                $(".bbox").css("display", "none");
            }
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

        scaleBBox(bbox) {
            // Create a new object with spread operator
            return {
                ...bbox,
                x: parseInt(bbox.x * this.scale),
                y: parseInt(bbox.y * this.scale),
                width: parseInt(bbox.width * this.scale),
                height: parseInt(bbox.height * this.scale)
            };
        },

        unscaleBBox(bbox) {
            return {
                ...bbox,
                x: parseInt(bbox.x / this.scale),
                y: parseInt(bbox.y / this.scale),
                width: parseInt(bbox.width / this.scale),
                height: parseInt(bbox.height / this.scale)
            };
        },

        async getCurrentBBox() {
            return await ky.get(`${this.baseURL}/getBBox`, {
                searchParams: {
                    idObject: this.object.idObject,
                }
            }).json();
        },

        displayBBox(scaledBBox) {
            // Initialize interaction handlers only once
            if (!this.interactionInitialized[scaledBBox.idBoundingBox]) {
                this.initializeBBoxInteraction(scaledBBox.idBoundingBox);
            }
            // bbox with screen dimensions
            this.drawBBox(scaledBBox);
            document.dispatchEvent(new CustomEvent("bbox-drawn", {
                detail: {
                    scaledBBox,
                }
            }));
        },

        // async updateExistingBBox(bboxId, newBBox) {
        //     this.bbox = newBBox;
        //     newBBox.idBoundingBox = bboxId;
        //     await this.onBBoxUpdate(newBBox);
        // },

        // async createNewBBox(newBBox) {
        //     newBBox.idBoundingBox = await ky.post(`${this.baseURL}/createBBox`, {
        //         json: {
        //             _token: this._token,
        //             idObject: this.object.idObject,
        //             frameNumber: this.currentFrame,
        //             bbox: newBBox
        //         }
        //     }).json();
        //     return newBBox;
        // },

        // async handleNonGroundTruthBBox(bbox) {
        //     console.log("Recreating non-ground truth bbox via tracking for frame", this.currentFrame);
        //     let previousBBox = this.bboxes[this.currentFrame - 1];
        //
        //     if (previousBBox) {
        //         let trackedBBox = await this.performTracking(previousBBox);
        //         let newBBox = this.createTrackedBBox(trackedBBox, bbox.blocked, false);
        //         newBBox.idBoundingBox = bbox.idBoundingBox; // Keep same ID, update position
        //
        //         await this.updateExistingBBox(bbox.idBoundingBox, newBBox);
        //         //this.showBBox(); // Refresh to show updated bbox
        //         this.displayBBox(bbox);
        //     } else {
        //         // No previous bbox to track from - use existing bbox as is
        //         console.log("No previous bbox for tracking, using existing bbox");
        //         this.displayBBox(bbox);
        //     }
        // },

        // async handleMissingBBox() {
        //     let previousBBox = this.bboxes[this.currentFrame - 1];
        //     if (previousBBox) {
        //         console.log("create new bbox via tracking on frame", this.currentFrame, previousBBox);
        //         let trackedBBox = await this.performTracking(previousBBox);
        //         let newBBox = this.createTrackedBBox(trackedBBox, previousBBox.blocked, false);
        //
        //         await this.createNewBBox(newBBox);
        //         return newBBox;
        //         //this.showBBox(); // Refresh to show new bbox
        //     } else {
        //         if (this.currentFrame !== this.object.startFrame) {
        //             messenger.notify("warning", "There is no previous BBox to tracking");
        //         }
        //         return null;
        //     }
        // },

        // async showBBox() {
        //     let bbox = await this.getCurrentBBox();
        //     if (!bbox) {
        //         bbox = await this.handleMissingBBox();
        //     }
        //     console.log("showBBox", bbox);
        //     if (bbox) {
        //         if (bbox.isGroundTruth) {
        //             // Ground truth bbox - use as is
        //             console.log("Using ground truth bbox for frame", this.currentFrame);
        //             this.displayBBox(bbox);
        //         } else {
        //             // Non-ground truth bbox - recreate via tracking
        //             await this.handleNonGroundTruthBBox(bbox);
        //         }
        //     }
        //     document.dispatchEvent(new CustomEvent("bbox-drawn", {
        //         detail: {
        //             bbox
        //         }
        //     }));
        // },

        onBBoxCreate() {
            this.clearBBox();
            this.onEnableDrawing();
            console.log("Drawing mode activated!");
        },

        initializeBBoxInteraction(idBoundingBox) {
            let bbox = $('#bbox_' + idBoundingBox);

            if (bbox.length === 0) return false; // No bbox element found

            let containerHeight = $("#boxesContainer").height();
            let containerWidth = $("#boxesContainer").width();
            // console.log("container", containerHeight, containerWidth);

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
                        this.currentBBox?.blocked || false,
                        idBoundingBox
                    );
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
                        this.currentBBox?.blocked || false,
                        idBoundingBox
                    );
                    this.onBBoxChange(bboxChanged);
                }
            });

            this.interactionInitialized[idBoundingBox] = true;
            // console.log("BBox interaction initialized once");
            return true;
        },

        clearBBox: function () {
            $(".bbox").css("display", "none");
        },

        drawBBox(scaledBBox) {
            // bbox with screen dimensions
            //let $dom = $(".bbox");
            let $dom = $("#bbox_" + scaledBBox.idBoundingBox);
            // console.log("drawBBox", bbox, $dom, this.bgColor);
            //$dom.css("display", "none");
            if (scaledBBox) {
                if (!this.hidden) {
                    $dom.css({
                        position: "absolute",
                        display: "block",
                        width: scaledBBox.width + "px",
                        height: scaledBBox.height + "px",
                        left: scaledBBox.x + "px",
                        top: scaledBBox.y + "px",
                        borderColor: this.bgcolors[scaledBBox.order - 1],
                        backgroundColor: "transparent",
                        opacity: 1
                    });

                    $dom.find(".objectId").css({
                        backgroundColor: this.bgcolors[scaledBBox.order - 1],
                        color: this.fgcolors[scaledBBox.order - 1],
                    });
                    $dom.find(".objectId").text(scaledBBox.order);

                    $dom.css({
                        borderColor: this.bgcolors[scaledBBox.order - 1],
                        borderStyle: "solid",
                        borderWidth: "4px"
                    });

                    // this.visible = true;
                    if (scaledBBox.blocked) {
                        $dom.css({
                            borderStyle: "dashed",
                            backgroundColor: this.bgcolors[scaledBBox.order - 1],
                            opacity: 0.4
                        });
                    }
                    $dom.css("display", "block");
                }
            }
        },


        handleMouseDown(e) {
            e.preventDefault();
            e.stopPropagation();

            let parent = document.getElementById("boxesContainer").parentElement;

            // this.startX = parseInt(e.clientX - this.offsetX);
            // this.startY = parseInt(e.clientY - this.offsetY);
            this.startX = parseInt(e.clientX - parent.offsetLeft);
            this.startY = parseInt(e.clientY - parent.offsetTop);
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

            let parent = document.getElementById("boxesContainer").parentElement;

            // this.mouseX = parseInt(e.clientX - this.offsetX);
            // this.mouseY = parseInt(e.clientY - this.offsetY);

            this.mouseX = parseInt(e.clientX - parent.offsetLeft);
            this.mouseY = parseInt(e.clientY - parent.offsetTop);

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
