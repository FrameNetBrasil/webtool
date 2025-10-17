document.addEventListener("alpine:init", () => {
    window.doStore = Alpine.store("doStore", {
        dataState: "",
        timeByFrame: 0,
        frameCount: 1,
        timeCount: 0,
        timeDuration: 0,
        frameDuration: 0,
        currentVideoState: "paused",
        currentFrame: 1,
        currentStartFrame: 0,
        currentEndFrame: 0,
        newStartFrame: 1,
        newEndFrame: 1,
        currentObject: null,
        currentObjectState: "none",
        newObjectState: "none",
        showHideBoxesState: "hide",
        // objects: [],
        layers: [],
        objectIndex: 0,
        init() {
            annotation.objects.init();
            annotation.timeline.init();
        },
        config() {
            let config = {
                idVideoDOMElement: annotation.video.idVideo,
                fps: annotation.video.fps
            };
            annotation.objects.config(config);
            annotation.drawBox.config(config);
        },
        timeFormated: (timeSeconds) => {
            let minute = Math.trunc(timeSeconds / 60);
            let seconds = Math.trunc(timeSeconds - (minute * 60));
            return minute + ":" + seconds;
        },
        // setTimelineTime: (timeMilliSeconds) => {
        //     annotation.timeline.setTime(timeMilliSeconds);
        // },
        // setObjects(objects) {
        //     this.objects = objects;
        // },
        initObjectIndex: () => {
            this.objectIndex = 0;
        },
        setLayers(layerList) {
            this.layers = layerList;
        },
        async loadLayerList() {
            this.dataState = "loading";
            await annotation.api.loadLayerList();
        },
        // async updateObjectList() {
        //     this.dataState = 'loading';
        //     await annotation.api.loadObjects();
        // },
        updateCurrentFrame(frameNumber) {
            //console.log('updateCurrentFrame',frameNumber,this.currentVideoState,this.newObjectState);
            this.frameCount = this.currentFrame = frameNumber;
            if ((this.currentVideoState === "paused") ||
                (this.newObjectState === "tracking") ||
                (this.newObjectState === "editing")
            ) {
                annotation.objects.drawFrameObject(frameNumber);
            }
        },
        selectObject(idObject, gotoFrame) {
            if (idObject === null) {
                this.currentObject = null;
                this.newObjectState = "none";
                $(".bbox").css("display", "none");
                htmx.ajax("GET", "/annotation/deixis/formAnnotation/0", "#formObject");
            } else {
                //console.log(" ** player current time - selectObject", annotation.video.player.currentTime());
                let object = annotation.objects.get(idObject);
                this.currentObject = object;
                this.currentStartFrame = object.startFrame;
                this.currentEndFrame = object.endFrame;
                // console.log('after', object,this.currentFrame);
                this.currentFrame = gotoFrame || this.currentStartFrame;
                annotation.video.gotoFrame(gotoFrame || this.currentStartFrame);
                this.newObjectState = "editing";
                //annotation.timeline.setTime(object.startTime);
                htmx.ajax("GET", "/annotation/deixis/formAnnotation/" + object.idDynamicObject, "#formObject");
            }
            // annotationGridObject.selectRowByObject(idObject);
        },
        selectObjectByIdDynamicObject(idDynamicObject, gotoFrame) {
            //console.log("getting", idDynamicObject);
            let object = annotation.objects.getByIdDynamicObject(idDynamicObject);
            console.log("after", object);
            this.selectObject(object.idObject, gotoFrame);
        },
        commentObject(idDynamicObject) {
            let object = annotation.objects.getByIdDynamicObject(idDynamicObject);
            this.selectObject(object.idObject);
            let context = {
                target: "#formObject",
                values: {
                    idDynamicObject,
                    order: object.idObject,
                    idDocument: annotation.document.idDocument
                }
            };
            htmx.ajax("GET", "/annotation/deixis/formComment", context);
        },
        createBBox() {
            if (this.currentObject.startFrame === this.currentFrame) {
                if (this.currentVideoState === "paused") {
                    //console.log('create object');
                    //this.selectObject(null);
                    this.newObjectState = "creating";
                    this.uiCreatingBBox();
                    annotation.objects.creatingBBox();
                }
            } else {
                manager.notify("error", "BBox not allowed.");
            }
        },
        async endBBox() {
            if (this.currentVideoState === "paused") {
                //console.log('end object');
                this.currentObject.endFrame = this.currentFrame;
                await annotation.objects.saveBBox(this.currentObject);
                //this.selectObject(null);
                this.newObjectState = "none";
            }
        },

        startTracking() {
            console.log("*** start tracking");
            this.newObjectState = "tracking";
            this.currentVideoState = "playing";
            this.uiStartTracking();
            annotation.objects.tracking(true);
        },
        pauseTracking() {
            console.log("pause tracking");
            this.newObjectState = "tracking";
            this.currentVideoState = "paused";
            this.uiPauseTracking();
        },
        async stopTracking() {
            console.log("stop tracking", this.currentObject.idObject);
            this.currentVideoState = "paused";
            this.newObjectState = "editing";
            this.uiEnableTracking();
            console.log("stopTracking ", this.currentObject);
            this.currentObject.endFrame = this.currentFrame;
            await annotation.objects.updateObjectFrame();
        },
        showHideObjects() {
            console.log("show/hide objects", this.showHideBoxesState);
            if (this.showHideBoxesState === "show") {
                this.showHideBoxesState = "hide";
            } else {
                this.showHideBoxesState = "show";
            }
            // show/hide todas as boxes existentes no currentFrame
            if (this.currentFrame < 1) {
                return;
            }
            if (this.showHideBoxesState === "hide") {
                $(".bbox").css("display", "none");
            } else {
                let objects = annotation.objects.tracker.annotatedObjects.filter(o => o.inFrame(this.currentFrame));
                console.log(objects);
                objects.forEach(o => {
                    o.drawBoxInFrame(this.currentFrame, "editing");
                });
            }
        },
        uiCreatingBBox() {
            document.getElementById("btnCreateObject").disabled=true;
            document.getElementById("btnStartTracking").disabled=true;
            document.getElementById("btnPauseTracking").disabled=true;
            document.getElementById("btnStopObject").disabled=true;
            document.getElementById("btnDeleteBBox").disabled=true;
            annotation.video.disablePlayPause();
            annotation.video.disableSkipFrame();
        },
        uiEditingObject() {
            console.log("editing object");
            document.getElementById("btnCreateObject").disabled=true;
            document.getElementById("btnDeleteBBox").disabled=true;
            document.getElementById("btnStartTracking").disabled=true;

            if (this.currentObject) {
                let hasBBox = this.currentObject.hasBBox();
                console.log(hasBBox);
                console.log(this.currentObject.startFrame,this.currentFrame);
                if ((this.currentObject.startFrame === this.currentFrame) && (!hasBBox)) {
                    document.getElementById("btnCreateObject").disabled=false;
                }
                if (hasBBox) {
                    document.getElementById("btnDeleteBBox").disabled=false;
                    document.getElementById("btnStartTracking").disabled=false;
                }
            }
            document.getElementById("btnPauseTracking").disabled=true;
            document.getElementById("btnStopObject").disabled=true;
            annotation.video.disablePlayPause();
            // annotation.video.disableSkipFrame();
        },
        uiEnableTracking() {
            document.getElementById("btnCreateObject").disabled=true;
            document.getElementById("btnStartTracking").disabled=false;
            document.getElementById("btnPauseTracking").disabled=true;
            document.getElementById("btnStopObject").disabled=true;
            document.getElementById("btnDeleteBBox").disabled=true;
        },
        uiStartTracking() {
            document.getElementById("btnCreateObject").disabled=true;
            document.getElementById("btnStartTracking").disabled=true;
            document.getElementById("btnPauseTracking").disabled=false;
            document.getElementById("btnStopObject").disabled=true;
            document.getElementById("btnDeleteBBox").disabled=true;
        },
        uiPauseTracking() {
            document.getElementById("btnCreateObject").disabled=true;
            document.getElementById("btnStartTracking").disabled=false;
            document.getElementById("btnPauseTracking").disabled=true;
            document.getElementById("btnStopObject").disabled=false;
            document.getElementById("btnDeleteBBox").disabled=true;
        },

    });

    Alpine.effect(() => {
        const timeByFrame = Alpine.store("doStore").timeByFrame;
        //console.log('timeByFrame change', timeByFrame);
    });
    Alpine.effect(() => {
        const frameCount = Alpine.store("doStore").frameCount;
        //console.log('framecount change', frameCount);
    });
    Alpine.effect(async () => {
        const currentVideoState = Alpine.store("doStore").currentVideoState;
        const newObjectState = Alpine.store("doStore").newObjectState;
        console.error("newobjectstate = " + newObjectState);
//        const newObjectStateTracking = (Alpine.store('doStore').newObjectState === 'tracking');
//         $("#btnCreateObject").addClass("disabled");
//         $("#btnStartTracking").addClass("disabled");
//         $("#btnPauseTracking").removeClass("disabled");
//         $("#btnStopObject").addClass("disabled");
//         $("#btnDeleteBBox").addClass("disabled");
        // if (currentVideoState === 'playing') {
        //     if (newObjectState === 'tracking') {
        //         $('#btnCreateObject').addClass('disabled');
        //         $('#btnStartTracking').addClass('disabled');
        //         $('#btnPauseTracking').removeClass('disabled');
        //         $('#btnStopObject').addClass('disabled');
        //         $('#btnDeleteBBox').addClass('disabled');
        //     } else {
        //         $('#btnCreateObject').addClass('disabled');
        //         $('#btnStartTracking').addClass('disabled');
        //         $('#btnPauseTracking').addClass('disabled');
        //         $('#btnStopObject').addClass('disabled');
        //         $('#btnDeleteBBox').addClass('disabled');
        //     }
        // }
        // if (currentVideoState === "paused") {
        //     if (newObjectState === "none") {
        //         if (Alpine.store("doStore").currentObject) {
        //             if (Alpine.store("doStore").currentObject.startFrame === Alpine.store("doStore").currentFrame) {
        //                 $("#btnCreateObject").removeClass("disabled");
        //             } else {
        //                 $("#btnCreateObject").addClass("disabled");
        //             }
        //         } else {
        //             $("#btnCreateObject").addClass("disabled");
        //         }
        //         $("#btnStartTracking").addClass("disabled");
        //         $("#btnPauseTracking").addClass("disabled");
        //         $("#btnStopObject").addClass("disabled");
        //         $("#btnDeleteBBox").addClass("disabled");
        //     } else if (newObjectState === "tracking") {
        //         $("#btnCreateObject").addClass("disabled");
        //         $("#btnStartTracking").addClass("disabled");
        //         $("#btnPauseTracking").removeClass("disabled");
        //         $("#btnStopObject").removeClass("disabled");
        //         $("#btnDeleteBBox").addClass("disabled");
        //     }
        // }
        // if (newObjectState === "editing") {
        //     let currentObject = Alpine.store("doStore").currentObject;
        //     if (currentObject) {
        //         console.log(currentObject.hasBBox);
        //         if ((currentObject.startFrame === Alpine.store("doStore").currentFrame) && (!currentObject.hasBBox)) {
        //             $("#btnCreateObject").removeClass("disabled");
        //         } else {
        //             $("#btnCreateObject").addClass("disabled");
        //         }
        //     } else {
        //         $("#btnCreateObject").addClass("disabled");
        //     }
        //     $("#btnStartTracking").addClass("disabled");
        //     $("#btnPauseTracking").addClass("disabled");
        //     $("#btnStopObject").addClass("disabled");
        //     $("#btnDeleteBBox").addClass("disabled");
        //     annotation.video.disablePlayPause();
        //     annotation.video.disableSkipFrame();
        // }
        // if (newObjectState === "creating") {
        //     $("#btnCreateObject").addClass("disabled");
        //     $("#btnStartTracking").addClass("disabled");
        //     $("#btnPauseTracking").addClass("disabled");
        //     $("#btnStopObject").addClass("disabled");
        //     $("#btnDeleteBBox").addClass("disabled");
        //     annotation.video.disablePlayPause();
        //     annotation.video.disableSkipFrame();
        // }
        if (newObjectState === "created") {
            await annotation.objects.createdBBox();
            Alpine.store("doStore").currentVideoState = "paused";
            annotation.video.enableSkipFrame();
        }
    });
    Alpine.effect(async () => {
        const dataState = Alpine.store("doStore").dataState;
        if (dataState === "loaded") {
            console.log("Data Loaded");
            window.annotation.objects.annotateObjects(annotation.layerList);
            window.annotation.timeline.updateModel();
            Alpine.store("doStore").setLayers(annotation.layerList);
            Alpine.store("doStore").newObjectState = "none";
            Alpine.store("doStore").currentVideoState = "paused";
            if (annotation.idDynamicObject) {
                setTimeout(function() {
                    Alpine.store("doStore").selectObjectByIdDynamicObject(annotation.idDynamicObject);
                }, 100);
            }
        }
    });
});
