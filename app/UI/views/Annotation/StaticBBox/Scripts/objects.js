/*
Annotation Objects
 */
annotation.objects = {
    list: [],
    colors: [
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
        "#f8b90d"
    ],
    boxesContainer: document.querySelector("#boxesContainer"),
    init: () => {
        console.error("initing objectManager");
        annotation.objects.clearAll();
    },
    add: (annotatedObject) => {
        annotation.objects.list.push(annotatedObject);
    },
    get: (idObject) => {
        return annotation.objects.list.find((o) => o.idObject === idObject);
    },
    getByIdStaticObject: (idStaticObject) => {
        return annotation.objects.list.find(o => o.object.idStaticObject === idStaticObject);
    },
    clearAll: () => {
        annotation.objects.list = [];
    },
    interactify: (annotatedObject, onChange) => {
        let dom = annotatedObject.dom;
        let bbox = $(dom);
        //bbox.addClass('bbox');
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
                console.error("resize position", position);
                onChange(Math.round(position.left), Math.round(position.top), Math.round(bbox.width()), Math.round(bbox.height()));
            }
        });
        i.addEventListener("click", function() {
            //dynamicStore.dispatch('selectObject', parseInt(this.innerHTML))
            let idObject = parseInt(this.innerHTML);
            Alpine.store("doStore").selectObject(idObject);
            //let currentObject = Alpine.store('doStore').currentObject;
            //htmx.ajax("GET","/annotation/dynamicMode/formObject/" + currentObject.object.idStaticObject + "/" + idObject, "#formObject");
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
                let width =  Math.round(bbox.width()) + 8; // width + 2*border-size
                let height =  Math.round(bbox.height()) + 8; // height + 2*border-size
                //console.error("stopdrag position", position);
                //onChange(Math.round(position.left), Math.round(position.top), Math.round(bbox.width()), Math.round(bbox.height()));
                onChange(Math.round(position.left), Math.round(position.top),width,height);

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
    annotateObjects: (objects) => {
        annotation.objects.clearAll();
        for (var object of objects) {
            console.log(object);
            let annotatedObject = new StaticBBoxObject(object);
            annotatedObject.dom = annotation.objects.newBboxElement();
            annotatedObject.scale = annotation.dimensions.scale;
            annotatedObject.idDocument = annotation.idDocument;
            if (object.bbox) {
                annotatedObject.loadBBox(object.bbox);
            }
            annotation.objects.add(annotatedObject);
            annotation.objects.interactify(
                annotatedObject,
                (x, y, width, height) => {
                    annotatedObject.bbox = new BoundingBox(x, y, width, height);
                    annotation.api.updateBBox({
                        idStaticObject: annotatedObject.object.idStaticObject,
                        bbox: annotatedObject.getScaledBBox()
                    });
                }
            );
        }
        console.log("objects annotated");
    },
    clearBBoxes: function() {
        $(".bbox").css("display", "none");
    },
    drawBoxes: function() {
        // show/hide todas as boxes
        let state = Alpine.store("doStore").showHideBoxesState;
        if (state === "hide") {
            $(".bbox").css("display", "none");
        } else {
            let objects = annotation.objects.list;
            console.log(objects);
            objects.forEach(o => {
                o.drawBox();
            });
        }
    },
    hideBoxes: function() {
        $(".bbox").css("display", "none");
    },
    showBoxes: function() {
        let objects = annotation.objects.list;
        console.log(objects);
        objects.forEach(o => {
            o.drawBox();
        });

    },
    creatingObject() {
        annotation.drawBox.init();
        console.log("creating new object");
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
        console.log(annotation.drawBox.box);
        let tempObject = {
            bbox: new BoundingBox(
                annotation.drawBox.box.x,
                annotation.drawBox.box.y,
                annotation.drawBox.box.width,
                annotation.drawBox.box.height
            ),
            dom: annotation.objects.newBboxElement()
        };
        console.log("tempObject", tempObject);
        let data = await annotation.objects.createNewObject(tempObject);
        console.log("after createNewObject", data);
        annotation.objects.showBoxes();
    },
    initializeNewObject: (annotatedObject) => {
        annotatedObject.object = {
            idFrame: -1,
            frame: "",
            idFE: -1,
            fe: ""
        };
        annotatedObject.scale = annotation.dimensions.scale;
        annotatedObject.visible = true;
        annotatedObject.hidden = false;
        annotatedObject.locked = false;
        annotatedObject.color = "white";
    },
    createNewObject: async (tempObject) => {
        try {
            console.log("createNewObject", tempObject);
            let annotatedObject = new StaticBBoxObject(null);
            annotatedObject.dom = tempObject.dom;
            annotatedObject.bbox = tempObject.bbox;
            annotation.objects.initializeNewObject(annotatedObject);
            annotation.objects.interactify(
                annotatedObject,
                (x, y, width, height, idBoundingBox) => {
                    console.error("interactify", x, y, width, height);
                    annotatedObject.bbox = new BoundingBox(x, y, width, height);
                    annotation.objects.saveRawObject(annotatedObject);
                }
            );
            console.log("##### creating newObject");
            let params = {
                idStaticObject: null,
                idFrame: null,
                idFrameElement: null,
                idLU: null
            };
            console.log("createNewObject", annotatedObject);
            let data = await annotation.objects.saveObject(annotatedObject, params);
            Alpine.store("doStore").selectObjectByIdStaticObject(data.idStaticObject);
            Alpine.store("doStore").newObjectState = "showing";
            manager.notify("success", "New object created.");
            return data;
        } catch (e) {
            Alpine.store("doStore").newObjectState = "none";
            manager.notify("error", e.message);
            console.log(e.message);
            return null;
        }
    },
    saveObject: async (currentObject, params) => {
        params.idDocument = annotation.document.idDocument;
        params.idStaticObject = currentObject.idStaticObject;
        params.bbox = currentObject.getScaledBBox();
        console.log("saveObject", currentObject, params);
        let data = await annotation.api.updateObject(params);
        console.log("object updated", data);
        await Alpine.store("doStore").updateObjectList();
        return data;
    },
    saveRawObject: async (currentObject) => {
        try {
            console.log("saving raw object #", currentObject.idObject);
            let params = {
                idDocument: annotation.document.idDocument,
                idStaticObject: currentObject.object.idStaticObject,
                idFrame: currentObject.object.idFrame,
                idFrameElement: currentObject.object.idFrameElement,
                idLU: currentObject.object.idLU,
                origin: 2,
                frames: []
            };
            annotation.objects.saveObject(currentObject, params);
        } catch (e) {
            Alpine.store("doStore").newObjectState = "none";
            console.log(e.message);
            return null;
        }

    },
    updateObject: async (data) => {
        let currentObject = Alpine.store("doStore").currentObject;
        let params = {
            idDocument: annotation.document.idDocument,
            idStaticObject: currentObject.object.idStaticObject,
            idFrameElement: parseInt(data.idFrameElement),
            idLU: parseInt(data.idLU)
        };
        await annotation.api.updateObject(params);
        await Alpine.store("doStore").updateObjectList();
        Alpine.store("doStore").selectObject(currentObject.idObject);
    },
    // updateObjectAnnotation: async (data) => {
    //     let currentObject = Alpine.store("doStore").currentObject;
    //     let params = {
    //         idDocument: annotation.document.idDocument,
    //         idStaticObject: currentObject.object.idStaticObject,
    //         idFrameElement: parseInt(data.idFrameElement),
    //         idLU: data.idLU ? parseInt(data.idLU) : null
    //     };
    //     await annotation.api.updateObjectAnnotation(params);
    //     await Alpine.store("doStore").updateObjectList();
    //     Alpine.store("doStore").selectObject(currentObject.idObject);
    // },
    updateObjectAnnotationEvent: async () => {
        let currentObject = Alpine.store("doStore").currentObject;
        await Alpine.store("doStore").updateObjectList();
        Alpine.store("doStore").selectObject(currentObject.idObject);
    },
    deleteObject: async (idStaticObject) => {
        console.log("deleting", idStaticObject);
        await messenger.confirmDelete(
            "Removing object #" + idStaticObject + ".",
            "/annotation/staticBBox/" + idStaticObject,
            async () => await Alpine.store("doStore").updateObjectList()
        );
    },
    deleteObjectComment: async (idStaticObject) => {
        await messenger.confirmDelete("Removing comment for object #" + idStaticObject + ".",
            "/annotation/staticBBox/comment/" + annotation.document.idDocument + "/" + idStaticObject,
            async () => {
                await Alpine.store("doStore").updateObjectList();
                let currentObject = Alpine.store("doStore").currentObject;
                Alpine.store("doStore").selectObject(currentObject.idObject);
            });
    },
    cloneCurrentObject: async () => {
        try {
            let currentObject = Alpine.store("doStore").currentObject;
            let params = {
                idDocument: annotation.document.idDocument,
                idStaticObject: currentObject.object.idStaticObject
            };
            annotation.api.cloneObject(params, async (object) => {
                console.log("after clone", object);
                await Alpine.store("doStore").updateObjectList();
                manager.notify("success", "Cloned object : #" + object.idStaticObject);
                Alpine.store("doStore").selectObjectByIdStaticObject(object.idStaticObject);
            });
        } catch (e) {
            console.log(e);
            return null;
        }
    }

};
