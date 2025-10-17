/**
 * Tracks a single dynamicModeObject throughout a frame sequence using optical flow.
 */
class ObjectTrackerObject {
    constructor(config) {
        this.framesManager = new FramesManager();
        this.annotatedObjects = [];

        // Initialize optical flow with defensive check
        try {
            this.opticalFlow = new OpticalFlowObject();
            console.log("🔧 OpticalFlow initialized in ObjectTrackerObject");
        } catch (error) {
            console.error("❌ Failed to initialize OpticalFlow:", error);
            this.opticalFlow = null;
        }

        this.lastFrame = -1;
        // this.framesManager.onReset.push(() => {
        //     this.annotatedObjects = [];
        //     this.lastFrame = -1;
        // });
    }

    config(config) {
        this.framesManager.setConfig(config);
    }

    ensureOpticalFlowInitialized() {
        if (!this.opticalFlow) {
            try {
                this.opticalFlow = new OpticalFlowObject();
                console.log("🔧 OpticalFlow re-initialized in ObjectTrackerObject");
                return true;
            } catch (error) {
                console.error("❌ Failed to re-initialize OpticalFlow:", error);
                return false;
            }
        }
        return true;
    }

    add(annotatedObject) {
        this.annotatedObjects.push(annotatedObject);
    }

    clearAll() {
        this.annotatedObjects = [];
    }

    async getFrameImage(frameNumber) {
        await this.framesManager.getFrameImage(frameNumber);
    }

    async setBBoxForObject(annotatedObject, frameNumber) {
        try {
            let bbox = annotatedObject.getBoundingBoxAt(frameNumber - 1);
            if (bbox) {
                await this.trackObject(frameNumber, annotatedObject);
            } else {
                throw new Error("Tracking must be done sequentially!");
            }
        } catch (e) {
            console.error(e.message);
            messenger.notify("error", e.message);
        }
    }

    // obtem os dados de um AnnotatedObject especifico, no frame frameNumber
    getFrameWithObject(frameNumber, annotatedObject) {
        return new Promise((resolve, _) => {
            //console.log(annotatedObject);
            try {
                let i = this.startFrameObject(frameNumber, annotatedObject);
                //console.log(' getFrameWithObjects startFrame = ' + i + '  annotatedObject = ' + annotatedObject.idObject);
//console.log('getFrameWithObjects frameNumber = ' + frameNumber + '  i = ' + i);
                let trackNextFrameObject = () => {
                    console.log(" ###### tracking frame " + i);
                    this.trackObject(i, annotatedObject)
                        .then((frameWithObjects) => {
                            if (i === frameNumber) {
                                //console.log('i == frameNumber')
                                console.log("frameWithObjects", frameWithObjects);
                                resolve(frameWithObjects);
                            } else {
                                i++;
                                //console.log('trackNextFrame i = ' + i)
                                trackNextFrameObject();
                            }
                        });
                };
                trackNextFrameObject();
            } catch (e) {
                console.error(e.message);
                messenger.notify("error", e.message);
            }
        });
    }

    startFrameObject(frameNumber, targetAnnotatedObject) {
        // procura o frame que tenha a box que possa servir de start para o tracker
        let start = frameNumber;
        if (!targetAnnotatedObject.inFrame(frameNumber)) { // objeto não está no frame frameNumber
            let hasStart = false;
            let objectStartFrame = frameNumber;
            while (targetAnnotatedObject.object.startFrame <= objectStartFrame) {
                // o objeto começa antes do frame frameNumber
                if (targetAnnotatedObject.getBoundingBoxAt(objectStartFrame) === null) {
                    // não tem bbox no frame objectStartFrame; passa para o anterior
                    objectStartFrame--;
                } else {
                    hasStart = true;
                    if (objectStartFrame < start) {
                        start = objectStartFrame;
                    }
                    break;
                }
            }
            if (!hasStart) {
                throw new Error("Corrupted object annotations");
            }
        }
        return start;
    }

    async trackObject(frameNumber, annotatedObject) {
        let currentImageData = await this.framesManager.getFrameImage(frameNumber);
        // let currentImageData = await vatic.blobToImage(blob);
        //let previousImageData = this.imageData();
        let bbox = annotatedObject.getBoundingBoxAt(frameNumber);
        if (bbox === null) {
            //não existe bbox para o AnnotatedObject no frame frameNumber
            bbox = annotatedObject.getBoundingBoxAt(frameNumber - 1);
            if (bbox == null) {
                // também não existe bbox no frame no anterior
                throw new Error("Tracking must be done sequentially!");
            } else {
                this.opticalFlow.reset();
                let previousImageData = await this.framesManager.getFrameImage(frameNumber - 1);
                //let previousImageData = await vatic.blobToImage(blob);
                // let previousImageData = this.imageData();
                this.opticalFlow.init(previousImageData);
                let bboxes = [{x:bbox.x,y:bbox.y,width:bbox.width,height:bbox.height}];
                let newBboxes = this.opticalFlow.track(currentImageData, bboxes);

                // Debug logging for enhanced optical flow
                if (window.opticalFlowDebugger) {
                    window.opticalFlowDebugger.logTrackingResult(bboxes, newBboxes, frameNumber);
                }

                let newBbox = new BoundingBox(frameNumber,newBboxes[0].x,newBboxes[0].y,newBboxes[0].width,newBboxes[0].height,false);
                console.log("Enhanced tracking - newBbox:",newBbox);
                console.log("Tracking stats:", this.opticalFlow.getTrackingStats());
                annotatedObject.addBBox(newBbox);
                //console.log("object.bboxes", annotatedObject.bboxes);
                //toCompute.push({ annotatedObject: annotatedObject, bbox: bbox });

            }
        }
    }

    async trackBBox(frameNumber, previousBbox) {
        // Ensure optical flow is properly initialized
        if (!this.ensureOpticalFlowInitialized()) {
            throw new Error("OpticalFlow initialization failed - cannot perform tracking");
        }

        let currentImageData = await this.framesManager.getFrameImage(frameNumber);
        // let currentImageData = await vatic.blobToImage(blob);
        //let previousImageData = this.imageData();
        // let bbox = annotatedObject.getBoundingBoxAt(frameNumber);
        // if (bbox === null) {
            //não existe bbox para o AnnotatedObject no frame frameNumber
            // bbox = annotatedObject.getBoundingBoxAt(frameNumber - 1);
            if (previousBbox == null) {
                // também não existe bbox no frame no anterior
                throw new Error("Tracking must be done sequentially!");
            } else {
                this.opticalFlow.reset();
                let previousImageData = await this.framesManager.getFrameImage(frameNumber - 1);
                //let previousImageData = await vatic.blobToImage(blob);
                // let previousImageData = this.imageData();
                this.opticalFlow.init(previousImageData);
                let bboxes = [{x:previousBbox.x,y:previousBbox.y,width:previousBbox.width,height:previousBbox.height}];
                let newBboxes = this.opticalFlow.track(currentImageData, bboxes);

                // Debug logging for enhanced optical flow
                if (window.opticalFlowDebugger) {
                    window.opticalFlowDebugger.logTrackingResult(bboxes, newBboxes, frameNumber);
                }

                let newBbox = new BoundingBox(frameNumber,newBboxes[0].x,newBboxes[0].y,newBboxes[0].width,newBboxes[0].height,false);
                console.log("Enhanced tracking - newBbox:",newBbox);
                console.log("Tracking stats:", this.opticalFlow.getTrackingStats());
                // annotatedObject.addBBox(newBbox);
                //console.log("object.bboxes", annotatedObject.bboxes);
                //toCompute.push({ annotatedObject: annotatedObject, bbox: bbox });
                return newBbox;

            }
        // }
    }

    trackObject_old(frameNumber, annotatedObject) {
        return new Promise((resolve, _) => {
            this.framesManager
                .getFrame(frameNumber)
                .then((blob) => {
                    //console.log('track framenumber ' + frameNumber);
                    //console.log(blob);
                    vatic.blobToImage(blob)
                        .then((img) => {
                            let result = [];
                            let toCompute = [];
                            //if (annotatedObject.inFrame(frameNumber)) {
                            // console.log('** tracking object ' + annotatedObject.idObject + '  frameNumber = ' + frameNumber)
                            let bbox = annotatedObject.getBoundingBoxAt(frameNumber);
                            //console.log(annotatedFrame);
                            if (bbox === null) {
                                //não existe bbox para o AnnotatedObject no frame frameNumber
                                // console.log('    não existe no frame ' + frameNumber);
                                bbox = annotatedObject.getBoundingBoxAt(frameNumber - 1);
                                if (bbox == null) {
                                    // também não existe bbox no frame no anterior
                                    // console.log('    não existe no frame anterior' + (frameNumber - 1));
                                    throw new Error("Tracking must be done sequentially!");
                                    //throw 'tracking must be done sequentially';
                                    //continue; // passa para o próximo AnnotatedObject
                                } else {
                                    //console.log('to compute');
                                    //console.log(annotatedFrame.bbox)
                                    // existe no frame anterior mas não no current,
                                    // então é preciso calcular a nova box
                                    // console.log('    existe no frame anterior mas não no corrente - calcular nova box');
                                    // console.log(frameObject.bbox);
                                    toCompute.push({ annotatedObject: annotatedObject, bbox: bbox });
                                }
                            } else {
                                // existe bbox para o AnnotatedObject no frame frameNumber
                                // console.log('    existe no frame corrente');
                                // console.log(frameObject.bbox)
                                if (bbox.x == null) {
                                    // console.log('    existe no frame corrente com bbox null - calcular nova box');
                                    bbox = annotatedObject.getBoundingBoxAt(frameNumber - 1);
                                    if (bbox == null) {
                                        // não existe bbox no frame anterior
                                        // console.log('    não existe no frame anterior ' + (frameNumber - 1));
                                        throw new Error("Tracking must be done sequentially!");
                                        //throw 'tracking must be done sequentially';
                                        //continue; // passa para o próximo AnnotatedObject
                                    }
                                    //console.log('to compute');
                                    //console.log(annotatedFrame.bbox)
                                    // existe no frame anterior mas não no corrent, então é preciso calcular a nova box
                                    // console.log('    existe no frame anterior - calcular nova box');
                                    // console.log(frameObject.bbox);
                                    toCompute.push({ annotatedObject: annotatedObject, bbox: bbox });
                                } else {
                                    // console.log('    existe no frame corrente, então coloco no result');
                                    result.push({ annotatedObject: annotatedObject, bbox: bbox });
                                }
                            }
                            //}
                            //}

                            let bboxes = toCompute.map(c => c.bbox);
                            //console.log(bboxes)
                            let hasAnyBbox = bboxes.some(bbox => bbox != null);

                            let optionalOpticalFlowInit;
                            if (hasAnyBbox) {
                                // se tem alguma box para ser calculada
                                optionalOpticalFlowInit = this.initOpticalFlow(frameNumber - 1);
                            } else {
                                optionalOpticalFlowInit = new Promise((r, _) => {
                                    r();
                                });
                            }

                            optionalOpticalFlowInit
                                .then(() => {
                                    let newBboxes;
                                    //console.log('hasanybbox',hasAnyBbox)
                                    if (hasAnyBbox) {
                                        //console.log('tracking lastFrame = ' + frameNumber)
                                        let imageData = this.imageData(img);
                                        newBboxes = this.opticalFlow.track(imageData, bboxes);
                                        this.lastFrame = frameNumber;
                                    } else {
                                        //console.log('newBboxes = bboxes');
                                        newBboxes = bboxes;
                                    }
                                    //console.log(newBboxes);
                                    for (let i = 0; i < toCompute.length; i++) {
                                        let annotatedObject = toCompute[i].annotatedObject;
                                        let bbox = new BoundingBox(frameNumber, newBboxes[i].x, newBboxes[i].y, newBboxes[i].width, newBboxes[i].height, false);
                                        // let frameObject = new Frame(frameNumber, newBboxes[i], false);
                                        // annotatedObject.addToFrame(frameObject);
                                        // result.push({ annotatedObject: annotatedObject, frameObject: frameObject });
                                        result.push({ annotatedObject: annotatedObject, bbox: bbox });
                                    }
                                    resolve({ img: img, objects: result });
                                });

                        });
                });
        });
    }


    initOpticalFlow(frameNumber) {
        return new Promise((resolve, _) => {
            //console.log('initOpticalFlow lastFrame = ' + this.lastFrame + '   frameNumber = ' + frameNumber);
            if (this.lastFrame !== -1 && this.lastFrame === frameNumber) {
                resolve();
            } else {
                this.opticalFlow.reset();
                //let blob = this.framesManager.getFrame(frameNumber);
                this.framesManager.getFrame(frameNumber)
                    .then((blob) => {
                        vatic.blobToImage(blob).then((img) => {
                            let imageData = this.imageData(img);
                            this.opticalFlow.init(imageData);
                            this.lastFrame = frameNumber;
                            resolve();
                        });
                    });
            }
        });
    }

    imageData() {
        return this.framesManager.ctx.getImageData(0, 0, this.framesManager.canvas.width, this.framesManager.canvas.height);
    }

}
