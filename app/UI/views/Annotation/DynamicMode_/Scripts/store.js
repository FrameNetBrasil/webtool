document.addEventListener("alpine:init", () => {
    window.doStore = Alpine.store("doStore", {
        dataState: "",
        timeByFrame: 0,
        frameCount: 1,
        timeDuration: 0,
        frameDuration: 0,
        currentVideoState: "paused",
        currentFrame: 1,
        currentObject: null,
        currentObjectState: "none",
        newObjectState: "none",
        showHideBoxesState: "hide",
        objects: [],
        init() {
            annotation.objects.init();
        },
        config() {
            let config = {
                idVideoDOMElement: annotation.video.idVideo,
                fps: annotation.video.fps
            };
            annotation.objects.config(config);
            annotation.drawBox.config(config);
        },
        setObjects(objects) {
            this.objects = objects;
        },
        async updateObjectList() {
            this.dataState = "loading";
            await annotation.api.loadObjects();
        },
        updateCurrentFrame(frameNumber) {
            console.log("updateCurrentFrame", frameNumber, this.currentVideoState, this.newObjectState);
            this.frameCount = this.currentFrame = frameNumber;
            if ((this.currentVideoState === "paused") ||
                (this.newObjectState === "tracking") ||
                (this.newObjectState === "showing")
            ) {
                annotation.objects.drawFrameObject(frameNumber);
            }
        },
        selectObject(idObject) {
            if (idObject === null) {
                this.currentObject = null;
                this.newObjectState = "none";
                htmx.ajax("GET", "/annotation/dynamicMode/formObject/0/0", "#formObject");
            } else {
                console.log(" ** player current time - selectObject", annotation.video.player.currentTime());
                let object = annotation.objects.get(idObject);
                this.currentObject = object;
                annotation.idDynamicObject = object.object.idDynamicObject;
                //console.log(object);
                //let time = annotation.video.timeFromFrame(object.object.startFrame);
                //console.log(time, object.object.startFrame);
                //annotation.video.player.currentTime(time);
                annotation.video.gotoFrame(object.object.startFrame);
                annotation.objects.drawFrameObject(object.object.startFrame);
                this.newObjectState = "showing";
                htmx.ajax("GET", "/annotation/dynamicMode/formObject/" + object.object.idDynamicObject + "/" + idObject, "#formObject");
            }
            // annotationGridObject.selectRowByObject(idObject);
        },
        selectObjectByIdDynamicObject(idDynamicObject) {
            //console.log('getting', idDynamicObject);
            let object = annotation.objects.getByIdDynamicObject(idDynamicObject);
            //console.log('after', object);
            this.selectObject(object.idObject);
        },
        commentObject(idDynamicObject) {
            let object = annotation.objects.getByIdDynamicObject(idDynamicObject);
            this.selectObject(object.idObject);
            //htmx.ajax("GET", "/annotation/dynamicMode/formComment/" + idDynamicObject + "/" + object.idObject, "#formObject");
            let context= {
                target: "#formObject",
                values: {
                    idDynamicObject,
                    order: object.idObject,
                    idDocument: annotation.document.idDocument
                }
            };
            htmx.ajax("GET", "/annotation/dynamicMode/formComment", context );
        },
        createObject() {
            if (this.currentVideoState === "paused") {
                //console.log('create object');
                this.selectObject(null);
                this.newObjectState = "creating";
                annotation.objects.creatingObject();
            }
        },
        async endObject() {
            if (this.currentVideoState === "paused") {
                //console.log('end object');
                this.currentObject.object.endFrame = this.currentFrame;
                await annotation.objects.saveObject(this.currentObject);
                this.selectObject(null);
            }
        },
        async deleteObject(idDynamicObject) {
            if (this.currentVideoState === "paused") {
                await annotation.api.deleteObject(idDynamicObject);
                await this.updateObjectList();
                this.selectObject(null);
            }
        },
        replaceByIdDynamicObject(idDynamicObject, object) {
            let index = this.objects.findIndex(o => o.idDynamicObject === idDynamicObject);
            const merged = Object.assign({}, this.objects[index], object);
            this.objects[index] = merged;
        },
        startTracking() {
            console.log("*** start tracking");
            this.newObjectState = "tracking";
            this.currentVideoState = "playing";
            annotation.objects.tracking(true);

        },
        pauseTracking() {
            console.log("pause tracking");
            this.newObjectState = "tracking";
            this.currentVideoState = "paused";
        },
        async stopTracking() {
            console.log("stop tracking", this.currentObject.idObject);
            this.currentVideoState = "paused";
            this.newObjectState = "showing";
            console.log("stopTracking ", this.currentObject);
            this.currentObject.object.endFrame = this.currentFrame;
            await annotation.objects.saveObject(this.currentObject);
            await this.updateObjectList();
            this.selectObject(this.currentObject.idObject);
        },
        clear() {
            console.log("clear");
            this.newObjectState = "none";
            this.selectObject(null);
            htmx.ajax("GET", "/annotation/dynamicMode/formObject/0/0", "#formObject");
        },
        showHideObjects() {
            console.log("show/hide objects", this.showHideBoxesState);
            if (this.showHideBoxesState === "show") {
                this.showHideBoxesState = "hide";
            } else {
                this.showHideBoxesState = "show";
            }
            //this.clear();
            //annotation.objects.drawFrameBoxes(this.currentFrame);
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
                    o.drawBoxInFrame(this.currentFrame, "showing");
                });
            }
        }
    });

    Alpine.effect(() => {
        const timeByFrame = Alpine.store("doStore").timeByFrame;
        //console.log('timeByFrame change', timeByFrame);
    });
    Alpine.effect(() => {
        const frameCount = Alpine.store("doStore").frameCount;
        //console.log('framecount change', frameCount);
    });
    Alpine.effect(() => {
        const currentVideoState = Alpine.store("doStore").currentVideoState;
        const newObjectStateTracking = (Alpine.store("doStore").newObjectState === "tracking");
        if (currentVideoState === "playing") {
            if (!newObjectStateTracking) {
                $("#btnCreateObject").addClass("disabled");
                $("#btnStartTracking").addClass("disabled");
                $("#btnPauseTracking").addClass("disabled");
                $("#btnEndObject").addClass("disabled");
                $("#btnStopObject").addClass("disabled");
                $("#btnShowHideObjects").addClass("disabled");
                $("#btnClear").addClass("disabled");
                let rate = annotation.video.player.playbackRate();
                if (rate > 0.9) {
                    Alpine.store("doStore").selectObject(null);
                }
            }
        }
        if (currentVideoState === "paused") {
            if (!newObjectStateTracking) {
                $("#btnCreateObject").removeClass("disabled");
                $("#btnShowHideObjects").removeClass("disabled");
                $("#btnClear").removeClass("disabled");
            }
        }
    });
    Alpine.effect(async () => {
        const newObjectState = Alpine.store("doStore").newObjectState;
        console.log("newobjectstate = " + newObjectState);
        if (newObjectState === "creating") {
            $("#btnCreateObject").addClass("disabled");
            $("#btnStartTracking").addClass("disabled");
            $("#btnPauseTracking").addClass("disabled");
            $("#btnEndObject").addClass("disabled");
            $("#btnStopObject").addClass("disabled");
            $("#btnShowHideObjects").addClass("disabled");
            annotation.video.disablePlayPause();
            annotation.video.disableSkipFrame();
        }
        if (newObjectState === "created") {
            await annotation.objects.createdObject();
            //Alpine.store('doStore').newObjectState = 'tracking';
            Alpine.store("doStore").currentVideoState = "paused";
            annotation.video.enableSkipFrame();
        }
        if (newObjectState === "showing") {
            $("#btnCreateObject").addClass("disabled");
            $("#btnStartTracking").removeClass("disabled");
            $("#btnPauseTracking").addClass("disabled");
            // $('#btnStopTracking').addClass('disabled');
            $("#btnEndObject").addClass("disabled");
            $("#btnStopObject").addClass("disabled");
            //$('#btnShowHideObjects').addClass('disabled');
            annotation.video.enablePlayPause();
            annotation.video.enableSkipFrame();
        }
        if (newObjectState === "tracking") {
            let pausedTracking = Alpine.store("doStore").currentVideoState === "paused";
            $("#btnCreateObject").addClass("disabled");
            if (pausedTracking) {
                $("#btnStartTracking").removeClass("disabled");
                //$('#btnStopTracking').removeClass('disabled');
                $("#btnPauseTracking").addClass("disabled");
                //$('#btnEndObject').removeClass('disabled');
                $("#btnStopObject").removeClass("disabled");
                $("#btnShowHideObjects").removeClass("disabled");
            } else {
                $("#btnStartTracking").addClass("disabled");
                // $('#btnStopTracking').removeClass('disabled');
                $("#btnPauseTracking").removeClass("disabled");
                $("#btnEndObject").addClass("disabled");
                $("#btnStopObject").addClass("disabled");
                $("#btnShowHideObjects").addClass("disabled");
            }
            annotation.video.disablePlayPause();
        }
        if (newObjectState === "none") {
            annotation.objects.clearFrameObject();
            $("#btnCreateObject").removeClass("disabled");
            $("#btnStartTracking").addClass("disabled");
            $("#btnPauseTracking").addClass("disabled");
            // $('#btnStopTracking').addClass('disabled');
            $("#btnEndObject").addClass("disabled");
            $("#btnStopObject").addClass("disabled");
            $("#btnShowHideObjects").removeClass("disabled");
            annotation.video.enablePlayPause();
        }
    });
    Alpine.effect(async () => {
        const dataState = Alpine.store("doStore").dataState;
        if (dataState === "loaded") {
            console.log("Data Loaded");
            console.log(annotation.objectList);
            window.annotation.objects.annotateObjects(annotation.objectList);
            Alpine.store("doStore").setObjects(annotation.objectList);
            Alpine.store("doStore").newObjectState = "none";
            Alpine.store("doStore").currentVideoState = "paused";
            if (annotation.idDynamicObject) {
                console.log(annotation.idDynamicObject);
                setTimeout(function() {
                    const elmnt = document.getElementById("do_" + annotation.idDynamicObject);
                    elmnt.scrollIntoView();
                    Alpine.store("doStore").selectObjectByIdDynamicObject(annotation.idDynamicObject);
                },100);

            }
        }
    });
});
