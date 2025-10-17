"use strict";

/**
 * Tracks annotated objects throughout a frame sequence using optical flow.
 * ematos@20211130 - alterado para fazer o tracker de apenas um AnnotatedObject de cada vez
 */
class ObjectsTracker {
    constructor(config) {
        this.framesManager = new FramesManager();
        this.annotatedObjects = [];
        this.opticalFlow = new OpticalFlow();
        this.lastFrame = -1;
        //this.ctx = document.createElement('canvas').getContext('2d');
        this.framesManager.onReset.push(() => {
            this.annotatedObjects = [];
            this.lastFrame = -1;
        });
    }

    config(config) {
        this.framesManager.setConfig(config);
    }

    add(annotatedObject) {
        this.annotatedObjects.push(annotatedObject)
    }

    /*
    getLength() {
        return this.annotatedObjects.length;
    }


    remove(i) {
        this.annotatedObjects.splice(i, 1);
    }

    clear(annotatedObject) {
        for (let i = 0; i < this.annotatedObjects.length; i++) {
            if (this.annotatedObjects[i].idObject === annotatedObject.idObject) {
                this.remove(i);
            }
        }
    }
*/
    clearAll() {
        this.annotatedObjects = [];
    }

    /*
        getObjects() {
            let result = [];
            for (let i = 0; i < this.annotatedObjects.length; i++) {
                let annotatedObject = this.annotatedObjects[i];
                let annotatedFrame = annotatedObject.get(1);
                result.push({annotatedObject: annotatedObject, annotatedFrame: annotatedFrame});
            }
            return result;
        }

        getObjectsByFrame(frameNumber) {
            let result = [];
            for (let i = 0; i < this.annotatedObjects.length; i++) {
                let annotatedObject = this.annotatedObjects[i];
                let annotatedFrame = annotatedObject.get(frameNumber);
                if (annotatedFrame != null) {
                    if (annotatedFrame.frameNumber === frameNumber) {
                        result.push({annotatedObject: annotatedObject, annotatedFrame: annotatedFrame});
                    }
                }
            }
            return result;
        }

        getFrameWithObjects(frameNumber) {
            return new Promise((resolve, _) => {
                let i = this.startFrame(frameNumber);
                console.log(' getFrameWithObjects startFrame = ' + i);
    //console.log('getFrameWithObjects frameNumber = ' + frameNumber + '  i = ' + i);
                let trackNextFrame = () => {
                    //console.log('tracking frame ' + i);
                    this.track(i).then((frameWithObjects) => {
                        if (i === frameNumber) {
                            //console.log('i == frameNumber')
                            resolve(frameWithObjects);
                        } else {
                            i++;
                            //console.log('trackNextFrame i = ' + i)
                            trackNextFrame();
                        }
                    });
                };
                trackNextFrame();
            });
        }
    */

    // obtem os dados de um AnnotatedObject especifico, no frame frameNumber
    getFrameWithObject(frameNumber, annotatedObject) {
        return new Promise((resolve, _) => {
            //console.log(annotatedObject);
            try {
                let i = this.startFrameObject(frameNumber, annotatedObject);
                //console.log(' getFrameWithObjects startFrame = ' + i + '  annotatedObject = ' + annotatedObject.idObject);
//console.log('getFrameWithObjects frameNumber = ' + frameNumber + '  i = ' + i);
                let trackNextFrameObject = () => {
                    console.log(' ###### tracking frame ' + i);
                    this.trackObject(i, annotatedObject)
                        .then((frameWithObjects) => {
                            if (i === frameNumber) {
                                //console.log('i == frameNumber')
                                resolve(frameWithObjects);
                            } else {
                                i++;
                                //console.log('trackNextFrame i = ' + i)
                                trackNextFrameObject();
                            }
                        });
                };
                trackNextFrameObject();
            } catch(e) {
                console.error(e.message);
                manager.notify("error", e.message);
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
                if (targetAnnotatedObject.getFrameAt(objectStartFrame) === null) {
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
                throw new Error('Corrupted object annotations');
            }
        }
        return start;
    }

    /*
        startFrame(frameNumber) {
            let start = frameNumber;
            for (let i = 0; i < this.annotatedObjects.length; i++) {
                let annotatedObject = this.annotatedObjects[i];
                if (annotatedObject.inFrame(frameNumber)) { // objeto está no frame
                    let hasStart = false;
                    let objectStart = frameNumber;
                    while (annotatedObject.startFrame <= objectStart) {
                        if (annotatedObject.get(objectStart) == null) { // mas tem dados da bbox
                            objectStart--;
                        } else {
                            hasStart = true;
                            if (objectStart < start) {
                                start = objectStart;
                            }
                            break;
                        }
                    }
                    if (!hasStart) {
                        throw new Error('Corrupted object annotations');
                    }
                }
            }
            //console.log('startFrame = ' + start);
            return start;
        }


        track(frameNumber) {
            return new Promise((resolve, _) => {
                this.framesManager.getFrame(frameNumber).then((blob) => {
                    //console.log('track framenumber ' + frameNumber);
                    //console.log(blob);
                    vatic.blobToImage(blob).then((img) => {
                        let result = [];
                        let toCompute = [];
                        for (let i = 0; i < this.annotatedObjects.length; i++) {
                            let annotatedObject = this.annotatedObjects[i];
                            if (annotatedObject.inFrame(frameNumber)) {
                                console.log('** tracking object ' + annotatedObject.idObject + '  frameNumber = ' + frameNumber)
                                let annotatedFrame = annotatedObject.get(frameNumber);
                                //console.log(annotatedFrame);
                                if (annotatedFrame == null) { // não existe o AnnotatedObject no frame frameNumber
                                    console.log( '    não existe no frame ' + frameNumber);
                                    annotatedFrame = annotatedObject.get(frameNumber - 1);
                                    if (annotatedFrame == null) { // também não existe no anterior
                                        console.log( '    não existe no frame ' + ( frameNumber - 1));
                                        //throw 'tracking must be done sequentially';
                                        continue; // passa para o próximo AnnotatedObject
                                    }
                                    //console.log('to compute');
                                    //console.log(annotatedFrame.bbox)
                                    // existe no frame anterior mas não no corrent, então é preciso calcular a nova box
                                    console.log( '    existe no frame anterior mas não no corrente - calcular nova box');
                                    console.log(annotatedFrame.bbox);
                                    toCompute.push({annotatedObject: annotatedObject, bbox: annotatedFrame.bbox});
                                } else {
                                    // existe o AnnotatedObject no frame frameNumber, então coloca-o no array result
                                    console.log( '    existe no frame corrente');
                                    console.log(annotatedFrame.bbox);
                                    if (annotatedFrame.bbox == null) {
                                        console.log( '    existe no frame corrent com bbox null - calcular nova box');
                                        annotatedFrame = annotatedObject.get(frameNumber - 1);
                                        if (annotatedFrame == null) {  //também não existe no anterior
                                            console.log( '    não existe no frame ' + ( frameNumber - 1));
                                            //throw 'tracking must be done sequentially';
                                            continue; // passa para o próximo AnnotatedObject
                                        }
                                        //console.log('to compute');
                                        //console.log(annotatedFrame.bbox)
                                        // existe no frame anterior mas não no corrent, então é preciso calcular a nova box
                                        console.log( '    existe no frame anterior - calcular nova box');
                                        console.log(annotatedFrame.bbox);
                                        toCompute.push({annotatedObject: annotatedObject, bbox: annotatedFrame.bbox});
                                    } else {
                                        console.log('    existe no frame corrente, então coloco no result');
                                        result.push({annotatedObject: annotatedObject, annotatedFrame: annotatedFrame});
                                    }
                                }
                            }
                        }

                        let bboxes = toCompute.map(c => c.bbox);
                        //console.log(bboxes)
                        let hasAnyBbox = bboxes.some(bbox => bbox != null);

                        let optionalOpticalFlowInit;
                        if (hasAnyBbox) { // se tem alguma box para ser calculada
                            optionalOpticalFlowInit = this.initOpticalFlow(frameNumber - 1);
                        } else {
                            optionalOpticalFlowInit = new Promise((r, _) => {
                                r();
                            });
                        }

                        optionalOpticalFlowInit.then(() => {
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
                                let annotatedFrame = new Frame(frameNumber, newBboxes[i], false);
                                annotatedObject.add(annotatedFrame);
                                result.push({annotatedObject: annotatedObject, annotatedFrame: annotatedFrame});
                            }
                            resolve({img: img, objects: result});
                        });
                    });
                });
            });
        }
        */

    trackObject(frameNumber, annotatedObject) {
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
                            let frameObject = annotatedObject.getFrameAt(frameNumber);
                            //console.log(annotatedFrame);
                            if (frameObject === null) {
                                //não existe o AnnotatedObject no frame frameNumber
                                // console.log('    não existe no frame ' + frameNumber);
                                frameObject = annotatedObject.getFrameAt(frameNumber - 1);
                                if (frameObject == null) {
                                    // também não existe no anterior
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
                                    toCompute.push({annotatedObject: annotatedObject, bbox: frameObject.bbox});
                                }
                            } else {
                                // existe o AnnotatedObject no frame frameNumber
                                // console.log('    existe no frame corrente');
                                // console.log(frameObject.bbox)
                                if (frameObject.bbox == null) {
                                    // console.log('    existe no frame corrente com bbox null - calcular nova box');
                                    frameObject = annotatedObject.get(frameNumber - 1);
                                    if (frameObject == null) {
                                        // não existe no anterior
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
                                    toCompute.push({annotatedObject: annotatedObject, bbox: frameObject.bbox});
                                } else {
                                    // console.log('    existe no frame corrente, então coloco no result');
                                    result.push({annotatedObject: annotatedObject, frameObject: frameObject});
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
                                        let frameObject = new Frame(frameNumber, newBboxes[i], false);
                                        annotatedObject.addToFrame(frameObject);
                                        result.push({annotatedObject: annotatedObject, frameObject: frameObject});
                                    }
                                    resolve({img: img, objects: result});
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

    imageData(img) {
        // let canvas = this.ctx.canvas;
        // canvas.width = img.width;
        // canvas.height = img.height;
        // this.ctx.drawImage(img, 0, 0);
        // return this.ctx.getImageData(0, 0, canvas.width, canvas.height);
        return this.framesManager.ctx.getImageData(0, 0, this.framesManager.canvas.width, this.framesManager.canvas.height);
    }

};
