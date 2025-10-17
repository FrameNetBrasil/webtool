annotation.objects = {
    tracker: null,
    boxesContainer: document.querySelector("#boxesContainer"),
    init: () => {
        console.error("initing objectManager");
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
        return annotation.objects.tracker.annotatedObjects.find(o => o.idDynamicObject === idDynamicObject);
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
        for (let [indexLayer, layer] of objectsFromServer.entries()) {
            for (let [indexObj, object] of layer.objects.entries()) {
                if ((object.startFrame >= annotation.video.framesRange.first) && (object.startFrame <= annotation.video.framesRange.last)) {
                    let annotatedObject = new DeixisObject(object);
                    annotatedObject.dom = annotation.objects.newBboxElement();
                    annotation.layerList[indexLayer].objects[indexObj] = annotatedObject;
                    annotation.objects.add(annotatedObject);
                    annotation.objects.interactify(
                        annotatedObject,
                        (x, y, width, height) => {
                            let currentFrame = Alpine.store("doStore").currentFrame;
                            let bbox = new BoundingBox(currentFrame, x, y, width, height, true);
                            annotatedObject.updateBBox(bbox);
                            //console.log("update annotated object bbox", bbox);
                            annotation.api.updateBBox({
                                idBoundingBox: bbox.idBoundingBox,
                                bbox: bbox
                            });
                        }
                    );
                    let bboxes = object.bboxes;
                    for (let j = 0; j < bboxes.length; j++) {
                        let bbox = object.bboxes[j];
                        let frameNumber = parseInt(bbox.frameNumber);
                        let isGroundThrough = true;// parseInt(topLeft.find('l').text()) == 1;
                        let x = parseInt(bbox.x);
                        let y = parseInt(bbox.y);
                        let w = parseInt(bbox.width);
                        let h = parseInt(bbox.height);
                        let newBBox = new BoundingBox(frameNumber, x, y, w, h, isGroundThrough, parseInt(bbox.idBoundingBox));
                        newBBox.blocked = (parseInt(bbox.blocked) === 1);
                        annotatedObject.addBBox(newBBox);
                    }
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
            //console.log("drawFrame " + frameNumber + " " + newObjectState);
            if (currentObject) {
                let isTracking = (newObjectState === "tracking");
                if (isTracking) {
                    // se está tracking, a box:
                    // - ou já existe (foi criada antes)
                    // - ou precisa ser criada
                    let bbox = currentObject.getBoundingBoxAt(frameNumber);
                    if (bbox) {
                        currentObject.drawBoxInFrame(frameNumber, "editing");
                    } else {
                        let tracker = annotation.objects.tracker;
                        await tracker.setBBoxForObject(currentObject, frameNumber);
                        let bbox = currentObject.getBoundingBoxAt(frameNumber);
                        let paramsBBox = {
                            idDynamicObject: currentObject.idDynamicObject,
                            frameNumber: frameNumber,
                            bbox: bbox
                        };
                        await annotation.api.createBBox(paramsBBox, async (idBoundingBox) => {
                            // console.log(idBoundingBox);
                            // console.log("new BoundingBox", idBoundingBox);
                            bbox.idBoundingBox = idBoundingBox;
                        });
                        // console.log("returning from tracker");
                        currentObject.drawBoxInFrame(frameNumber, "editing");
                    }

                } else {
                    // console.log("drawFrame not tracking", currentObject);
                    currentObject.drawBoxInFrame(frameNumber, "editing");
                }
                // console.log("%%%%%%  end drawFrameObject in ", frameNumber);
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
                o.drawBoxInFrame(frameNumber, "editing");
            });
        }
    },
    creatingBBox() {
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
    async createdBBox() {
        console.log("new box created");
        document.querySelector("#canvas").style.cursor = "default";
        $("#canvas").off("mousedown");
        $("#canvas").off("mousemove");
        $("#canvas").off("mouseup");
        $("#canvas").off("mouseout");
        // console.log(annotation.drawBox.box);
        let tempObject = {
            bbox: new BoundingBox(0, annotation.drawBox.box.x, annotation.drawBox.box.y, annotation.drawBox.box.width, annotation.drawBox.box.height),
            dom: annotation.objects.newBboxElement()
        };
        let data = await annotation.objects.createNewBBox(tempObject);
        console.log("end CreatedBBox");
    },
    createNewBBox: async (tempObject) => {
        try {
            let currentFrame = Alpine.store("doStore").currentFrame;
            if (currentFrame === 0) {
                currentFrame = 1;
            }
            console.log("createNewBBox", tempObject, currentFrame);
            let annotatedObject = Alpine.store("doStore").currentObject;
            annotatedObject.dom = tempObject.dom;
            let bbox = new BoundingBox(currentFrame, tempObject.bbox.x, tempObject.bbox.y, tempObject.bbox.width, tempObject.bbox.height, true, null);
            console.log(annotatedObject);
            annotatedObject.addBBox(bbox);
            annotation.objects.interactify(
                annotatedObject,
                async (x, y, width, height, idBoundingBox) => {

                    let currentFrame = Alpine.store("doStore").currentFrame;
                    let bbox = new BoundingBox(currentFrame, x, y, width, height, true);
                    annotatedObject.updateBBox(bbox);
                    //console.log("update annotated object bbox", bbox);
                    //let bbox = annotatedObject.getBoundingBoxAt(currentFrame);
                    annotation.api.updateBBox({
                        idBoundingBox: bbox.idBoundingBox,
                        bbox: bbox
                    });
                }
            );
            //console.log("##### creating newBBox");
            //let data = await annotation.objects.createBBox(annotatedObject);
            let paramsBBox = {
                idDynamicObject: annotatedObject.idDynamicObject,
                frameNumber: currentFrame,
                bbox: bbox
            };
            console.log("##### creating new BBox");
            await annotation.api.createBBox(paramsBBox, async (idBoundingBox) => {
                console.log(idBoundingBox);
                console.log("new BoundingBox", idBoundingBox);
                bbox.idBoundingBox = idBoundingBox;
            });
            //await Alpine.store("doStore").loadLayerList();
            console.log("##### New bbox created.");
            Alpine.store("doStore").newObjectState = "tracking";
            annotation.objects.tracker.getFrameImage(currentFrame);
            annotatedObject.drawBoxInFrame(currentFrame, "editing");
            Alpine.store("doStore").uiEnableTracking();
            manager.notify("success", "New bbox created.");
            // return data;
        } catch (e) {
            Alpine.store("doStore").newObjectState = "none";
            Alpine.store("doStore").currentVideoState = "paused";
            manager.notify("error", e.message);
            console.log(e.message);
            // return null;
        }
    },
    updateObjectFrame: async () => {
        let currentObject = Alpine.store("doStore").currentObject;
        let params = {
            idDocument: annotation.document.idDocument,
            idDynamicObject: currentObject.idDynamicObject,
            startFrame: currentObject.startFrame,
            endFrame: currentObject.endFrame
        };
        annotation.api.updateObjectFrame(params, async (idDynamicObject) => {
            Alpine.store("doStore").selectObjectByIdDynamicObject(idDynamicObject);
        });
    },
    updateObjectAnnotation: async (data) => {
        let currentObject = Alpine.store("doStore").currentObject;
        let params = {
            idDocument: annotation.document.idDocument,
            idDynamicObject: currentObject.idDynamicObject,
            idFrameElement: data.idFrameElement ? parseInt(data.idFrameElement) : null,
            idLU: data.idLU ? parseInt(data.idLU) : null,
            idGenericLabel: data.idGenericLabel ? parseInt(data.idGenericLabel) : null
        };
        annotation.api.updateObjectAnnotation(params, async (dynamicObject) => {
            await Alpine.store("doStore").loadLayerList();
            Alpine.store("doStore").selectObjectByIdDynamicObject(dynamicObject.idDynamicObject);
        });
    },
    updateObjectRange: async () => {
        let currentObject = Alpine.store("doStore").currentObject;
        let params = {
            idDocument: annotation.document.idDocument,
            idDynamicObject: currentObject.idDynamicObject,
            startFrame: Alpine.store("doStore").currentStartFrame,
            endFrame: Alpine.store("doStore").currentEndFrame
        };
        annotation.api.updateObjectRange(params, async (idDynamicObject) => {
            await Alpine.store("doStore").loadLayerList();
            Alpine.store("doStore").selectObjectByIdDynamicObject(idDynamicObject);
        });
    },
    updateObjectAnnotationEvent: async () => {
        let currentObject = Alpine.store("doStore").currentObject;
        await Alpine.store("doStore").loadLayerList();
        Alpine.store("doStore").selectObject(currentObject.idObject);
    },
    deleteObject: async (idDynamicObject) => {
        console.log("delete ", idDynamicObject);
        await messenger.confirmDelete("Removing object #" + idDynamicObject + ".", "/annotation/deixis/" + idDynamicObject, async () => {
            await Alpine.store("doStore").loadLayerList();
            Alpine.store("doStore").selectObject(null);
        });
    },
    deleteObjectComment: async (idDynamicObject) => {
        await messenger.confirmDelete("Removing comment for object #" + idDynamicObject + ".",
            "/annotation/deixis/comment/" + annotation.document.idDocument + "/" + idDynamicObject,
            async () => {
                await Alpine.store("doStore").updateObjectList();
                let currentObject = Alpine.store("doStore").currentObject;
                Alpine.store("doStore").selectObject(currentObject.idObject);
            });
    },
    async tracking(canGoOn) {
        if (canGoOn) {
            let currentFrame = Alpine.store("doStore").currentFrame;
            let currentStartFrame = Alpine.store("doStore").currentStartFrame;
            let currentEndFrame = Alpine.store("doStore").currentEndFrame;
            console.log("range", annotation.video.framesRange);
            if (((currentFrame >= currentStartFrame) && (currentFrame < currentEndFrame))) {
                currentFrame = currentFrame + 1;
                console.log("tracking....", currentFrame);
                annotation.video.gotoFrame(currentFrame);
                //Alpine.store("doStore").updateCurrentFrame(currentFrame);
                await new Promise(r => setTimeout(r, 800));
                return annotation.objects.tracking(Alpine.store("doStore").currentVideoState === "playing");
            } else {
                Alpine.store("doStore").stopTracking();
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
    },
    createNewObjectAtLayer: async (params) => {
        if (params.idLayerType > 0) {
            params.idDocument = annotation.document.idDocument;
            annotation.api.createNewObjectAtLayer(params, async (object) => {
                console.log("after create new", object);
                await Alpine.store("doStore").loadLayerList();
                manager.notify("success", "New Object : #" + object.idDynamicObject);
                Alpine.store("doStore").selectObjectByIdDynamicObject(object.idDynamicObject);
            });
        } else {
            manager.notify("error", "No layer defined.");
        }
    },
    closeEdition: async () => {
        await htmx.ajax("GET", "/annotation/deixis/formAnnotation/0", "#formObject");
        Alpine.store("doStore").selectObject(null);
        this.newObjectState = "none";
        annotation.video.enablePlayPause();
        annotation.video.enableSkipFrame();
    },
    deleteBBox: async (idDynamicObject) => {
        let params = {
            idDynamicObject: idDynamicObject
        };
        annotation.api.deleteBBox(params, async (idDynamicObject) => {
            await Alpine.store("doStore").loadLayerList();
            Alpine.store("doStore").selectObjectByIdDynamicObject(idDynamicObject);
        });
    },


};
