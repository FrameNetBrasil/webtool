annotation.objects = {
    tracker: null,
    boxesContainer: document.querySelector("#boxesContainer"),
    init: () => {
        console.log("initing objectManager");
        annotation.objects.tracker = new ObjectsTracker();
    },
    config: (config) => {
        annotation.objects.tracker.config(config);
    },

    add: (annotatedObject) => {
        annotation.objects.tracker.add(annotatedObject);
    },
    /*
    push: (annotatedObject) => {
        dynamicObjects.tracker.add(annotatedObject);
    },
    */
    get: (idObject) => {
        return annotation.objects.tracker.annotatedObjects.find((o) => o.idObject === idObject);
    },
    getByIdDynamicObject: (idDynamicObject) => {
        //console.log("get", annotation.objects.tracker.annotatedObjects);
        return annotation.objects.tracker.annotatedObjects.find(o => o.object.idDynamicObject === idDynamicObject);
    },
    /*
    clear: (annotatedObject) => {
        dynamicObjects.tracker.clear(annotatedObject);
    },
    */
    clearAll: () => {
        annotation.objects.tracker.clearAll();
    },
    interactify: (annotatedObject, onChange) => {
        /*
            registra os listeners para interação com a boundingbox (dom) associada com o objeto
         */
        let dom = annotatedObject.dom;
        let bbox = $(dom);
        let createHandleDiv = (className, content = null) => {
            //console.log('className = ' + className + '  content = ' + content);
            let handle = document.createElement("div");
            handle.className = className;
            bbox.append(handle);
            if (content !== null) {
                handle.innerHTML = content;
            }
            return handle;
        };
        let x = createHandleDiv("handle center-drag");
        let i = createHandleDiv("objectId", annotatedObject.idObject);
        bbox.resizable({
            handles: "n, e, s, w",
            onStopResize: (e) => {
                let position = bbox.position();
                onChange(Math.round(position.left), Math.round(position.top), Math.round(bbox.width()), Math.round(bbox.height()));
            }
        });
        i.addEventListener("click", function() {
            let idObject = parseInt(this.innerHTML);
            Alpine.store("doStore").selectObject(idObject);
        });
        bbox.draggable({
            handle: $(x),
            onDrag: (e) => {
                var d = e.data;
                if (d.left < 0) {
                    d.left = 0;
                }
                if (d.top < 0) {
                    d.top = 0;
                }
                if (d.left + $(d.target).outerWidth() > $("#canvas").width()) {
                    d.left = $("#canvas").width() - $(d.target).outerWidth();
                }
                if (d.top + $(d.target).outerHeight() > $("#canvas").height()) {
                    d.top = $("#canvas").height() - $(d.target).outerHeight();
                }
            },
            onStopDrag: (e) => {
                let position = bbox.position();
                // console.log("stopdrag position", position);
                onChange(Math.round(position.left), Math.round(position.top), Math.round(bbox.width()), Math.round(bbox.height()));
            }
        });
        bbox.css("display", "none");
    },
    newBboxElement: () => {
        let dom = document.createElement("div");
        dom.className = "bbox";
        annotation.objects.boxesContainer.appendChild(dom);
        return dom;
    },

    annotateObjects: (objectsFromServer) => {
        annotation.objects.clearAll();
        for (var object of objectsFromServer) {
            if ((object.startFrame >= annotation.video.framesRange.first) && (object.startFrame <= annotation.video.framesRange.last)) {
                let annotatedObject = new DynamicObject(object);
                annotatedObject.dom = annotation.objects.newBboxElement();
                annotation.objects.add(annotatedObject);
                annotation.objects.interactify(
                    annotatedObject,
                    (x, y, width, height) => {
                        let currentFrame = Alpine.store("doStore").currentFrame;
                        let bbox = new BoundingBox(currentFrame, x, y, width, height, true);
//                        annotatedObject.addBBox(bbox);
                        annotatedObject.updateBBox(bbox);
                        //console.log("annotated object bbox", bbox);
                        annotation.api.updateBBox({
                            idBoundingBox: bbox.idBoundingBox,
                            bbox: bbox
                        });
                    }
                );
                let bboxes= object.bboxes;
                for (let j = 0; j < bboxes.length; j++) {
                    let bbox = object.bboxes[j];
                    let frameNumber = parseInt(bbox.frameNumber);
                    let isGroundThrough = true;// parseInt(topLeft.find('l').text()) == 1;
                    let x = parseInt(bbox.x);
                    let y = parseInt(bbox.y);
                    let w = parseInt(bbox.width);
                    let h = parseInt(bbox.height);
                    let newBBox = new BoundingBox(frameNumber, x, y, w, h,isGroundThrough,parseInt(bbox.idBoundingBox));
                    //let idBoundingBox = parseInt(polygon.idBoundingBox);
                    //let frameObject = new Frame(frameNumber, bbox, isGroundThrough, idBoundingBox);
                    newBBox.blocked = (parseInt(bbox.blocked) === 1);
                    annotatedObject.addBBox(newBBox);
                }
            }
        }
        console.log("objects annotated");
    },
    clearFrameObject: function() {
        $(".bbox").css("display", "none");
    },
    drawFrameObject: async function(frameNumber) {
        // desenha a box do objeto atual correspondente ao frame indicado por frameNumber
        //let that = this;
        frameNumber = parseInt(frameNumber);
        if (frameNumber < 1) {
            return;
        }
        try {
            let newObjectState = Alpine.store("doStore").newObjectState;
            // apaga todas as boxes
            $(".bbox").css("display", "none");
            let currentObject = Alpine.store("doStore").currentObject;
            console.log("drawFrame " + frameNumber + " " + newObjectState);
            if (currentObject) {
                let isTracking = (newObjectState === "tracking");
                if (isTracking) {
                    // se está tracking, a box:
                    // - ou já existe (foi criada antes)
                    // - ou precisa ser criada
                    let bbox = currentObject.getBoundingBoxAt(frameNumber);
                    if (bbox) {
                        currentObject.drawBoxInFrame(frameNumber, "showing");
                    } else {
                        let tracker = annotation.objects.tracker;
                        await tracker.setBBoxForObject(currentObject, frameNumber);
                        let bbox = currentObject.getBoundingBoxAt(frameNumber);
                        let paramsBBox = {
                            idDynamicObject: currentObject.object.idDynamicObject,
                            frameNumber: frameNumber,
                            bbox: bbox
                        };
                        await annotation.api.createBBox(paramsBBox, async(idBoundingBox) => {
                            console.log(idBoundingBox);
                            console.log("new BoundingBox", idBoundingBox);
                            bbox.idBoundingBox = idBoundingBox;
                        });
                        console.log("returning from tracker");
                        currentObject.drawBoxInFrame(frameNumber, "showing");
                    }

                    // tracker.getFrameWithObject(frameNumber, currentObject)
                    //     .then(async (frameWithObjects) => {
                    //         console.log("frameWithObject", frameWithObjects);
                    //         console.log("frameNumber", frameNumber);
                    //         currentObject.drawBoxInFrame(frameNumber, "tracking");
                    //         // let frameObject = currentObject.getFrameAt(frameNumber);
                    //         // let bbox = frameObject.bbox;
                    //         //bbox.blocked = frameObject.blocked;
                    //         //let bbox = currentObject.getBoundingBoxAt(frameNumber);
                    //         let bbox = frameWithObjects[0].bbox;
                    //         currentObject.addBBox(bbox);
                    //         console.log("***  tracker then ", bbox);
                    //         if (bbox.idBoundingBox) {
                    //             await annotation.api.updateBBox({
                    //                 idBoundingBox: bbox.idBoundingBox,
                    //                 bbox: bbox
                    //             });
                    //         } else {
                    //             let params = {
                    //                 idDynamicObject: currentObject.object.idDynamicObject,
                    //                 frameNumber: bbox.frameNumber,
                    //                 bbox: bbox
                    //             };
                    //             await annotation.api.createBBox(params, async(idBoundingBox) => {
                    //                 console.log(idBoundingBox);
                    //                 console.log("new BoundingBox", idBoundingBox);
                    //                 bbox.idBoundingBox = idBoundingBox;
                    //             });
                    //         }
                    //         console.log('==========');
                    //     });
                    //that.$store.commit('redrawFrame', false);
                } else {
                    console.log("drawFrame not tracking", currentObject);
                    currentObject.drawBoxInFrame(frameNumber, "showing");
                }
                console.log("%%%%%%  end drawFrameObject in ", frameNumber);
            }
        } catch (e) {
            console.error(e.message);
            manager.notify("error", e.message);
        }
    },
    drawFrameBoxes: function(frameNumber) {
        // show/hide todas as boxes existentes no frame frameNumber
        //let that = this;
        frameNumber = parseInt(frameNumber);
        if (frameNumber < 1) {
            return;
        }
        let state = Alpine.store("doStore").showHideBoxesState;
        if (state === "hide") {
            $(".bbox").css("display", "none");
        } else {
            let objects = annotation.objects.tracker.annotatedObjects.filter(o => o.inFrame(frameNumber));
            // console.log(objects);
            objects.forEach(o => {
                o.drawBoxInFrame(frameNumber, "showing");
            });
        }
    },
    creatingObject() {
        //annotation.video.player.currentTime(Alpine.store('doStore').timeByFrame);
        annotation.drawBox.init();
        // console.log("creating new object");
        document.querySelector("#canvas").style.cursor = "crosshair";
        $("#canvas").on("mousedown", function(e) {
            annotation.drawBox.handleMouseDown(e);
        });
        $("#canvas").on("mousemove", function(e) {
            annotation.drawBox.handleMouseMove(e);
        });
        $("#canvas").on("mouseup", function(e) {
            annotation.drawBox.handleMouseUp(e);
        });
        $("#canvas").on("mouseout", function(e) {
            annotation.drawBox.handleMouseOut(e);
        });
    },
    async createdObject() {
        console.log("new box created");
        document.querySelector("#canvas").style.cursor = "default";
        $("#canvas").off("mousedown");
        $("#canvas").off("mousemove");
        $("#canvas").off("mouseup");
        $("#canvas").off("mouseout");
        // console.log(annotation.drawBox.box);
        let tempObject = {
            bbox: new BoundingBox(0,annotation.drawBox.box.x, annotation.drawBox.box.y, annotation.drawBox.box.width, annotation.drawBox.box.height),
            dom: annotation.objects.newBboxElement()
        };
        let data = await annotation.objects.createNewObject(tempObject);
        console.log("end CreatedObject");
    },
    initializeNewObject: (annotatedObject, currentFrame) => {
        //console.log(annotatedObject);
        annotatedObject.object = {
            idFrame: -1,
            frame: "",
            idFE: -1,
            fe: "",
            startFrame: currentFrame,
            //endFrame: annotation.video.framesRange.last
            endFrame: currentFrame
        };
        annotatedObject.visible = true;
        annotatedObject.hidden = false;
        annotatedObject.locked = false;
        annotatedObject.color = "white";
    },
    createNewObject: async (tempObject) => {
        try {
            let currentFrame = Alpine.store("doStore").currentFrame;
            if (currentFrame === 0) {
                currentFrame = 1;
            }
            console.log("createNewObject", tempObject, currentFrame);
            let annotatedObject = new DynamicObject(null);
            annotatedObject.dom = tempObject.dom;
            let bbox = new BoundingBox(currentFrame,tempObject.bbox.x, tempObject.bbox.y, tempObject.bbox.width, tempObject.bbox.height, true, null);
            // let frameObject = new Frame(currentFrame, tempObject.bbox, true, null);
            // annotatedObject.addToFrame(frameObject);
            annotatedObject.addBBox(bbox);
            annotation.objects.initializeNewObject(annotatedObject, currentFrame);
            annotation.objects.interactify(
                annotatedObject,
                async (x, y, width, height, idBoundingBox) => {
                    let currentFrame = Alpine.store("doStore").currentFrame;
                    let bbox = new BoundingBox(currentFrame, x, y, width, height, true);
                    annotatedObject.updateBBox(bbox);
                    annotation.api.updateBBox({
                        idBoundingBox: bbox.idBoundingBox,
                        bbox: bbox
                    });
                }
            );
            console.log("##### creating newObject");

            let data = await annotation.objects.createObject(annotatedObject);
            let paramsBBox = {
                idDynamicObject: data.idDynamicObject,
                frameNumber: currentFrame,
                bbox: bbox
            };
            console.log("##### creating new BBox");
            await annotation.api.createBBox(paramsBBox, async(idBoundingBox) => {
                console.log(idBoundingBox);
                console.log("new BoundingBox", idBoundingBox);
                bbox.idBoundingBox = idBoundingBox;
            });
            await Alpine.store("doStore").updateObjectList();
            console.log("##### New object created.");
            Alpine.store("doStore").selectObjectByIdDynamicObject(data.idDynamicObject);
            //Alpine.store("doStore").newObjectState = "tracking";
            //Alpine.store("doStore").newObjectState = "created";
            //manager.messager("success", "New object created.");
            annotation.objects.tracker.getFrameImage(currentFrame);
            manager.notify("success", "New object created.");
            return data;
        } catch (e) {
            Alpine.store("doStore").newObjectState = "none";
            Alpine.store("doStore").currentVideoState = "paused";
            manager.notify("error", e.message);
            console.log(e.message);
            return null;
        }
    },
    // getObjectFrameData: (currentObject, startFrame, endFrame) => {
    //     console.log("getObjectFrameData", currentObject, startFrame, endFrame);
    //     let data = [];
    //     //let lastFrame = currentObject.endFrame;
    //     let lastFrame = startFrame;
    //     for (var frame of currentObject.frames) {
    //         if ((frame.frameNumber >= startFrame) && (frame.frameNumber <= endFrame)) {
    //             if (frame.bbox !== null) {
    //                 data.push({
    //                     frameNumber: frame.frameNumber,
    //                     frameTime: annotation.video.timeFromFrame(frame.frameNumber),
    //                     x: frame.bbox.x,
    //                     y: frame.bbox.y,
    //                     width: frame.bbox.width,
    //                     height: frame.bbox.height,
    //                     blocked: frame.blocked ? 1 : 0
    //                 });
    //                 lastFrame = frame.frameNumber;
    //             }
    //         }
    //     }
    //     return {
    //         frames: data,
    //         lastFrame: lastFrame
    //     };
    // },

    createObject: async (object) => {
        let params = {
            idDocument: annotation.document.idDocument,
            idDynamicObject: null,
            startFrame: object.object.startFrame,
            endFrame: object.object.endFrame,
            idFrame: null,
            idFrameElement: null,
            idLU: null,
            startTime: annotation.video.timeFromFrame(object.object.startFrame),
            endTime: annotation.video.timeFromFrame(object.object.endFrame),
            origin: 2
        };
        console.log("createObject", object, params);
        if (params.startFrame > params.endFrame) {
            throw new Error("endFrame must be greater or equal to startFrame.");
        }
        let data = await annotation.api.updateObject(params);
        console.log("object created", data);

//        await Alpine.store("doStore").updateObjectList();
        return data;
    },
    saveObject: async (currentObject) => {
        try {
            console.log("saving object #", currentObject.idObject);
            let params = {
                idDocument: annotation.document.idDocument,
                idDynamicObject: currentObject.object.idDynamicObject,
                startFrame: currentObject.object.startFrame,
                endFrame: currentObject.object.endFrame,
                idFrame: currentObject.object.idFrame,
                idFrameElement: currentObject.object.idFrameElement,
                idLU: currentObject.object.idLU,
                startTime: annotation.video.timeFromFrame(currentObject.object.startFrame),
                endTime: annotation.video.timeFromFrame(currentObject.object.endFrame),
                origin: 2,
                frames: []
            };
            let data = await annotation.api.updateObject(params);
            console.log("object saved", data);
            // annotation.objects.saveObject(currentObject, params);
        } catch (e) {
            Alpine.store("doStore").newObjectState = "none";
            Alpine.store("doStore").currentVideoState = "paused";
            console.log(e.message);
            return null;
        }

    },
    // updateObjectAnnotation: async (data) => {
    //     let currentObject = Alpine.store("doStore").currentObject;
    //     let params = {
    //         idDocument: annotation.document.idDocument,
    //         idDynamicObject: currentObject.object.idDynamicObject,
    //         idFrameElement: parseInt(data.idFrameElement),
    //         startFrame: parseInt(data.startFrame),
    //         endFrame: parseInt(data.endFrame),
    //         idLU: data.idLU ? parseInt(data.idLU) : null
    //     };
    //     await annotation.api.updateObjectAnnotation(params);
    //     console.log('* updateObjectAnnotation');
    //     //await Alpine.store("doStore").updateObjectList();
    //     Alpine.store("doStore").selectObject(currentObject.idObject);
    // },
    updateObjectAnnotationEvent: async () => {
        let currentObject = Alpine.store("doStore").currentObject;
        // console.log('* updateObjectAnnotationEvent' , currentObject);
        // let idDynamicObject = currentObject.object.idDynamicObject;
        await Alpine.store("doStore").updateObjectList();
        Alpine.store("doStore").selectObject(currentObject.idObject);
        // const object = await annotation.api.loadObject(idDynamicObject);
        // console.log("object loaded", object);
        // Alpine.store("doStore").replaceByIdDynamicObject(idDynamicObject, object);
    },
    deleteObject: async (idDynamicObject) => {
        await messenger.confirmDelete("Removing object #" + idDynamicObject + ".", "/annotation/dynamicMode/" + idDynamicObject, async () => {
            await Alpine.store("doStore").updateObjectList();
            Alpine.store("doStore").selectObject(null);
        });
    },
    deleteObjectComment: async (idDynamicObject) => {
        await messenger.confirmDelete("Removing comment for object #" + idDynamicObject + ".",
            "/annotation/dynamicMode/comment/" + annotation.document.idDocument + "/" + idDynamicObject,
            async () => {
            await Alpine.store("doStore").updateObjectList();
            let currentObject = Alpine.store("doStore").currentObject;
            Alpine.store("doStore").selectObject(currentObject.idObject);
        });
    },
    async tracking(canGoOn) {
        if (canGoOn) {
            let currentFrame = Alpine.store("doStore").currentFrame;
            console.log("range", annotation.video.framesRange);
            if (((currentFrame >= annotation.video.framesRange.first) && (currentFrame < annotation.video.framesRange.last))) {
                currentFrame = currentFrame + 1;
                console.log("tracking....", currentFrame);
                annotation.video.gotoFrame(currentFrame);
                //Alpine.store("doStore").updateCurrentFrame(currentFrame);
                await new Promise(r => setTimeout(r, 800));
                return annotation.objects.tracking(Alpine.store("doStore").currentVideoState === "playing");
            }
        }
    },
    cloneCurrentObject: async () => {
        let currentObject = Alpine.store("doStore").currentObject;
        let params = {
            idDocument: annotation.document.idDocument,
            idDynamicObject: currentObject.object.idDynamicObject
        };
        let object = await annotation.api.cloneObject(params);
        console.log(object);
        await Alpine.store("doStore").updateObjectList();
        manager.notify("success", "Cloned object : #" + object.idDynamicObject);
        Alpine.store("doStore").selectObjectByIdDynamicObject(object.idDynamicObject);
    }

    // async cloneObject(idObject) {
    //     let sourceObject = annotation.objects.get(idObject);
    //     let cloneObject = new DynamicObject(sourceObject.object);
    //     cloneObject.cloneFrom(sourceObject);
    //     let params = {
    //         idDocument: annotation.document.idDocument,
    //         //idObjectMM: null,
    //         idDynamicObject: null,
    //         startFrame: cloneObject.object.startFrame,
    //         endFrame: cloneObject.object.endFrame,
    //         idFrame: null,
    //         idFrameElement: null,
    //         idLU: null,
    //         startTime: annotation.video.timeFromFrame(cloneObject.object.startFrame),
    //         endTime: annotation.video.timeFromFrame(cloneObject.object.endFrame),
    //         origin: 2,
    //         frames: cloneObject.object.frames,
    //     };
    //     let data = await annotation.objects.saveObject(cloneObject, params);
    //     Alpine.store('doStore').selectObjectByIdDynamicObject(data.idDynamicObject);
    //     manager.messager("success", "Object cloned.");
    // }
};
