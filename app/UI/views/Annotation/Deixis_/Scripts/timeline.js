//var outlineContainer = document.getElementById("outline-container");

var keyframeWithCustomImage = {
    val: 500
};
annotation.timeline = {
    model: null,
    timeline: null,
    defaultKeyframesRenderer: null,
    playing: false,
    playStep: 50,
    // Automatic tracking should be turned off when user interaction happened.
    trackTimelineMovement: false,
    outlineContainer: null,
    generateModel: function(layerList) {
        var object;
        let rows = [];
        let groups = {};
        let groupsEmpty = {
            style: {
                fillColor: "transparent",
                marginTop: 3,
                cursor: "pointer",
                strokeColor: "white",
                strokeThickness: 1
            },
            keyframesStyle: {
                shape: "rect"
            }
        };
        for (var layer of layerList) {
            let element = {
                title: layer.layer,
                draggable: false
            };
            //let objects = annotation.objectList[layer];
            let objects = layer.objects;
            // create groups
            for (object of objects) {
                if (object.idGenericLabel) {
                    groups[object.idDynamicObject] = {
                        style: {
                            fillColor: object.bgColorGL,
                            textColor: object.fgColorGL,
                            marginTop: 3,
                            cursor: "pointer"
                        },
                        keyframesStyle: {
                            shape: "rect"
                        },
                        label: object.gl + (object.comment ? '*' : '')
                    };
                } else if (object.idLU) {
                    console.log(object);
                    groups[object.idDynamicObject] = {
                        style: {
                            fillColor: "white",
                            textColor: "black",
                            marginTop: 3,
                            cursor: "pointer"
                        },
                        keyframesStyle: {
                            shape: "rect"
                        },
                        label: object.lu + (object.fe ? '/' + object.frame + '.' + object.fe : '') + (object.comment ? '*' : '')
                    };
                } else {
                    // groups[object.idDynamicObject] = group[1];
                    groups[object.idDynamicObject] = {
                        style: {
                            fillColor: "transparent",
                            marginTop: 3,
                            cursor: "pointer",
                            strokeColor: "white",
                            strokeThickness: 1,
                            textColor: "white"

                        },
                        keyframesStyle: {
                            shape: "rect"
                        },
                        label: object.idDynamicObject + (object.comment ? '*' : '')
                    };
                }

            }
            // create keyframes
            let keyframes = [];
            for (object of objects) {
                keyframes.push({
                    val: object.startTime,
                    group: groups[object.idDynamicObject],
                    idDynamicObject: object.idDynamicObject,
                    position: "start",
                    max: object.endTime
                    //group: object.idDynamicObject
                });
                keyframes.push({
                    val: object.endTime,
                    group: groups[object.idDynamicObject],
                    idDynamicObject: object.idDynamicObject,
                    position: "end"
                    //group: object.idDynamicObject
                });
            }
            element.keyframes = keyframes;
            rows.push(element);
        }
        //console.log(rows);
        let timelineModel = {
            rows: rows
        };
        return timelineModel;
    },
    updateModel: function() {
        let timelineModel = annotation.timeline.generateModel(annotation.layerList);
        let timeline = annotation.timeline.timeline;
        timeline.setModel(timelineModel);
        annotation.timeline.generateHTMLOutlineListNodes(timelineModel.rows);
    },
    config: function() {
        annotation.timeline.timeline.onTimeChanged(function(event) {
            let timeline = annotation.timeline.timeline;
            let time = timeline.getTime();
        });
        annotation.timeline.timeline.onSelected(function(obj) {
            console.log("Selected Event: (" + obj.selected.length + "). changed selection :" + obj.changed.length, 2);
        });

        annotation.timeline.timeline.onDragStarted(function(obj) {
            //console.log(obj, "dragstarted");
        });

        annotation.timeline.timeline.onDrag(function(obj) {
            //console.log(obj, "drag");
        });

        annotation.timeline.timeline.onKeyframeChanged(function(obj) {
            console.log("keyframe: " + obj.val, obj.val / 1000);
            annotation.video.gotoTime(obj.val / 1000);
        });

        annotation.timeline.timeline.onDragFinished(function(obj) {
            if (obj.elements[0].type === "keyframe") {
                let keyframe = obj.elements[0].keyframe;
                //console.log("keyframe", keyframe);
                let currentFrame = Alpine.store("doStore").currentFrame;
                let currentObject = Alpine.store("doStore").currentObject;
                if (keyframe.position === "start") {
                    Alpine.store("doStore").currentStartFrame = currentFrame;
                    currentObject.startFrame = currentFrame;
                }
                if (keyframe.position === "end") {
                    obj.elements[0].row.keyframes[0].max = obj.elements[0].row.keyframes[1].val;
                    Alpine.store("doStore").currentEndFrame = currentFrame;
                    currentObject.endFrame = currentFrame;
                }
                annotation.objects.updateObjectFrame();
            }
            console.log("dragfinished");
        });

        annotation.timeline.timeline.onMouseDown(function(obj) {
            //console.log(obj.target, obj.val);
            var type = obj.target ? obj.target.type : "";
            if (obj.pos) {
                //console.log("mousedown:" + obj.val + ".  target:" + type + ". " + Math.floor(obj.pos.x) + "x" + Math.floor(obj.pos.y), 2);
                if (type === "") {
                    //console.log(obj.elements[1].keyframes[0].idDynamicObject);
                    if (obj.elements[1]) {
                        Alpine.store("doStore").currentFrame = annotation.video.frameFromTime(obj.val / 1000);
                        let idDynamicObject = obj.elements[1].keyframes[0].idDynamicObject;
                        Alpine.store("doStore").selectObjectByIdDynamicObject(idDynamicObject);
                    } else {
                        Alpine.store("doStore").selectObject(null);
                    }
                }
                if (type === "keyframe") {
                    Alpine.store("doStore").currentFrame = annotation.video.frameFromTime(obj.val / 1000);
                    //console.log(obj.elements[1].keyframes[0].idDynamicObject);
                    let idDynamicObject = obj.elements[1].keyframes[0].idDynamicObject;
                    Alpine.store("doStore").selectObjectByIdDynamicObject(idDynamicObject);
                }
            }
        });

        // annotation.timeline.timeline.onDoubleClick(function(obj) {
        //     var type = obj.target ? obj.target.type : "";
        //     if (obj.pos) {
        //         //console.log("doubleclick:" + obj.val + ".  target:" + type + ". " + Math.floor(obj.pos.x) + "x" + Math.floor(obj.pos.y), 2);
        //     }
        // });

        // Synchronize component scroll renderer with HTML list of the nodes.
        annotation.timeline.timeline.onScroll(function(obj) {
            let timeline = annotation.timeline.timeline;
            var options = timeline.getOptions();
            if (options) {
                if (annotation.timeline.outlineContainer) {
                    annotation.timeline.outlineContainer.style.minHeight = obj.scrollHeight + "px";
                    const outlineElement = document.getElementById("outline-scroll-container");
                    if (outlineElement) {
                        outlineElement.scrollTop = obj.scrollTop;
                    }
                }
            }
            annotation.timeline.showActivePositionInformation();
        });

        annotation.timeline.timeline.onScrollFinished(function(_) {
            // Stop move component screen to the timeline when user start manually scrolling.
            //console.log("on scroll finished", 2);
        });

    },
    init: function() {
        annotation.timeline.outlineContainer = document.getElementById("outline-container");
        //annotation.timeline.model = annotation.timeline.generateModel();
        annotation.timeline.timeline = new timelineModule.Timeline();
        let timelineModel = {
            rows: []
        };
        annotation.timeline.timeline.initialize({ id: "timeline", headerHeight: 45 }, timelineModel);
        annotation.timeline.timeline.setOptions({
            groupsDraggable: false,
            keyframesDraggable: true,
            timelineDraggable: false,
            rowsStyle: {
                height: 24
            }
        });
        annotation.timeline.config();
        annotation.timeline.timeline._formatUnitsText = (val)=> {

            return annotation.video.frameFromTime(Math.trunc(val /1000));
        };
    },
    setTime: function(timeMilliSeconds) {
        let timeline = annotation.timeline.timeline;
        timeline.setTime(timeMilliSeconds);
        annotation.timeline.moveTimelineIntoTheBounds();
    },
    showActivePositionInformation: function() {
        let timeline = annotation.timeline.timeline;
        //console.log("time changed", timeline);
        if (timeline) {
            var fromPx = timeline.scrollLeft;
            var toPx = timeline.scrollLeft + timeline.getClientWidth();
            var fromMs = timeline.pxToVal(fromPx - timeline._leftMargin());
            var toMs = timeline.pxToVal(toPx - timeline._leftMargin());
            var positionInPixels = timeline.valToPx(timeline.getTime()) + timeline._leftMargin();
            var message = "Timeline in ms: " + timeline.getTime() + "ms. Displayed from:" + fromMs.toFixed() + "ms to: " + toMs.toFixed() + "ms.";
            message += "<br>";
            message += "Timeline in px: " + positionInPixels + "px. Displayed from: " + fromPx + "px to: " + toPx + "px";
            //console.log(message);
            var currentElement = document.getElementById("currentTime");
            if (currentElement) {
                currentElement.innerHTML = message;
            }
        }
    },
    /**
     * Generate html for the left menu for each row.
     * */
    generateHTMLOutlineListNodes: function(rows) {
        let timeline = annotation.timeline.timeline;
        var options = timeline.getOptions();
        var headerElement = document.getElementById("outline-header");
        if (!headerElement) {
            return;
        }
        headerElement.style.maxHeight = headerElement.style.minHeight = options.headerHeight + "px";
        // headerElement.style.backgroundColor = options.headerFillColor;
        if (!annotation.timeline.outlineContainer) {
            console.log("Error: Cannot find html element to output outline/tree view");
            return;
        }
        annotation.timeline.outlineContainer.innerHTML = "";
        let marginBottom = ((options.rowsStyle ? options.rowsStyle.marginBottom : 0) || 0);
        rows.forEach(function(row, index) {
            var div = document.createElement("div");
            div.classList.add("outline-node");
            const h = (row.style ? row.style.height : 0) || (options.rowsStyle ? options.rowsStyle.height : 0);
            div.style.maxHeight = div.style.minHeight = h + "px";
            div.style.marginBottom = marginBottom + "px";
            div.innerText = row.title || "Track " + index;
            div.id = div.innerText;
            var alreadyAddedWithSuchNameElement = document.getElementById(div.innerText);
            // Combine outlines with the same name:
            if (alreadyAddedWithSuchNameElement) {
                var increaseSize = Number.parseInt(alreadyAddedWithSuchNameElement.style.maxHeight) + h + marginBottom;
                alreadyAddedWithSuchNameElement.style.maxHeight = alreadyAddedWithSuchNameElement.style.minHeight = increaseSize + "px";

                return;
            }
            if (annotation.timeline.outlineContainer) {
                annotation.timeline.outlineContainer.appendChild(div);
            }

        });
    },

// Handle events from html page
    selectMode: function() {
        let timeline = annotation.timeline.timeline;
        if (timeline) {
            timeline.setInteractionMode("selection");
        }
    },

    zoomMode: function() {
        let timeline = annotation.timeline.timeline;
        if (timeline) {
            timeline.setInteractionMode("zoom");
        }
    },

    noneMode: function() {
        let timeline = annotation.timeline.timeline;
        if (timeline) {
            timeline.setInteractionMode("none");
        }
    },

    removeKeyframe: function() {
        let timeline = annotation.timeline.timeline;
        if (timeline) {
            // Add keyframe
            const currentModel = timeline.getModel();
            if (currentModel && currentModel.rows) {
                currentModel.rows.forEach((row) => {
                    if (row.keyframes) {
                        row.keyframes = row.keyframes.filter((p) => !p.selected);
                    }
                });
                timeline.setModel(currentModel);
            }
        }
    },

    addKeyframe: function() {
        let timeline = annotation.timeline.timeline;
        if (timeline) {
            // Add keyframe
            const currentModel = timeline.getModel();
            if (!currentModel) {
                return;
            }
            currentModel.rows.push({ keyframes: [{ val: timeline.getTime() }] });
            timeline.setModel(currentModel);

            // Generate outline list menu
            annotation.timeline.generateHTMLOutlineListNodes(currentModel.rows);
        }
    },

    panMode: function(interactive) {
        let timeline = annotation.timeline.timeline;
        if (timeline) {
            timeline.setInteractionMode(interactive ? "pan" : "nonInteractivePan");
        }
    },

// Set scroll back to timeline when mouse scroll over the outline
    outlineMouseWheel: function(event) {
        let timeline = annotation.timeline.timeline;
        if (timeline) {
            this.timeline._handleWheelEvent(event);
        }
    },
    onPlayClick: function(event) {
        let timeline = annotation.timeline.timeline;
        annotation.timeline.playing = true;
        annotation.timeline.trackTimelineMovement = true;
        if (timeline) {
            annotation.timeline.moveTimelineIntoTheBounds();
            // Don't allow to manipulate timeline during playing (optional).
            timeline.setOptions({ timelineDraggable: false });
        }
    },

    onPauseClick: function(event) {
        let timeline = annotation.timeline.timeline;
        annotation.timeline.playing = false;
        if (timeline) {
            timeline.setOptions({ timelineDraggable: true });
        }
    },

    moveTimelineIntoTheBounds: function() {
        // console.log("moveTimelineIntoTheBounds");
        let timeline = annotation.timeline.timeline;
        if (timeline) {
            // console.log(timeline._startPosMouseArgs, timeline._scrollAreaClickOrDragStarted);
            // if (timeline._startPosMouseArgs || timeline._scrollAreaClickOrDragStarted) {
            //     // User is manipulating items, don't move screen in this case.
            //     console.log('not here');
            //     return;
            // }
            const fromPx = timeline.scrollLeft;
            const toPx = timeline.scrollLeft + timeline.getClientWidth();

            let positionInPixels = timeline.valToPx(timeline.getTime()) + timeline._leftMargin();
            // Scroll to timeline position if timeline is out of the bounds:
            //console.log(timeline.getTime(), positionInPixels, fromPx, toPx);
            if (positionInPixels <= fromPx || positionInPixels >= toPx) {
                this.timeline.scrollLeft = positionInPixels;
            }
        }
    }

    // initPlayer: function() {
    //     let timeline = annotation.timeline.timeline;
    //     setInterval(() => {
    //         if (annotation.timeline.playing) {
    //             if (timeline) {
    //                 timeline.setTime(timeline.getTime() + playStep);
    //                 annotation.timeline.moveTimelineIntoTheBounds();
    //             }
    //         }
    //     }, annotation.timeline.playStep);
    // }
};

// Custom Image
const image = new Image();
image.src = "https://material-icons.github.io/material-icons-png/png/white/public/baseline-2x.png"; // replace with your image path
image.onload = () => {
    // annotation.timeline.timeline.redraw();
};


// timeline.initialize({ id: "timeline", headerHeight: 45 }, timelineModel);

// Select all elements on key down
// document.addEventListener("keydown", function(args) {
//     if (args.which === 65 && timeline._controlKeyPressed(args)) {
//         timeline.selectAllKeyframes();
//         args.preventDefault();
//     }
// });


//generateHTMLOutlineListNodes(timelineModel.rows);


// Note: this can be any other player: audio, video, svg and etc.
// In this case you have to synchronize events of the component and player.
// initPlayer();
// showActivePositionInformation();
// window.onresize = showActivePositionInformation;

// const existingModel = timeline.getModel();
// existingModel.rows[0].keyframes.append({ val: 20 });
// timeline.setModel(existingModel);
// console.log("time", timeline.getTime());
// let change  = timeline.setTime(120000);
// moveTimelineIntoTheBounds();
// console.log("change", change);
// console.log("time", timeline.getTime());
