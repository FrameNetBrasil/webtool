/**
 * Represents a dynamic object that has bounding boxes throughout the entire frame sequence.
 */
class DeixisObject {
    constructor(object) {
        // this.object = object;
        Object.assign(this, object);
        if (object.order > 0) {
            this.idObject = parseInt(object.order);
            this.color = vatic.getColor(object.order);
        }
        this.visible = true;
        this.hidden = false;
        this.locked = false;
        this.dom = null;
    }

    hasBBox() {
       return (this.bboxes.length > 0);
    }

    inFrame(frameNumber) {
        return (this.object.startFrame <= frameNumber) && (this.object.endFrame >= frameNumber);
    }

    getBoundingBoxAt(frameNumber) {
        for (let i = 0; i < this.bboxes.length; i++) {
            let currentBBox = this.bboxes[i];
            if (currentBBox.frameNumber > frameNumber) {
                break;
            }
            if (currentBBox.frameNumber === frameNumber) {
                return currentBBox;
            }
        }
        return null;
    }

    drawBoxInFrame(frameNumber, state) {
        this.dom.style.display = "none";
        let bbox = this.getBoundingBoxAt(frameNumber);
        if (bbox) {
            console.log(state, this.hidden ? " hidden" : " not hidden");
            if (!this.hidden) {
                if (bbox.isVisible()) {
                    this.dom.style.position = "absolute";
                    this.dom.style.display = "block";
                    this.dom.style.width = bbox.width + "px";
                    this.dom.style.height = bbox.height + "px";
                    this.dom.style.left = bbox.x + "px";
                    this.dom.style.top = bbox.y + "px";

                    if (state === "tracking") {
                        let color = vatic.getColor(0);
                        this.dom.style.borderColor = color.bg;
                        this.dom.querySelector(".objectId").style.backgroundColor = color.bg;
                        this.dom.querySelector(".objectId").style.color = color.fg;
                        this.dom.style.borderStyle = "dotted";
                        this.dom.style.borderWidth = "2px";
                    }
                    if (state === "editing") {
                        this.dom.style.borderColor = this.color.bg;
                        this.dom.querySelector(".objectId").style.backgroundColor = this.color.bg;
                        this.dom.querySelector(".objectId").style.color = this.color.fg;
                        this.dom.style.borderStyle = "solid";
                        this.dom.style.borderWidth = "4px";
                    }
                    this.dom.style.backgroundColor = "transparent";
                    this.dom.style.opacity = 1;
                    this.visible = true;
                    if (bbox.blocked) {
                        this.dom.style.opacity = 0.5;
                        this.dom.style.backgroundColor = "white";
                        this.dom.style.borderStyle = "dashed";
                    }
                } else {
                    this.dom.style.display = "none";
                    this.visible = false;
                }
            }
        }

    }


    addBBox(bbox) {
        // console.log("addBBox",bbox);
        for (let i = 0; i < this.bboxes.length; i++) {
            if (this.bboxes[i].frameNumber === bbox.frameNumber) {
                bbox.idBoundingBox = this.bboxes[i].idBoundingBox;
                this.bboxes[i] = bbox;
                // console.log(i, annotatedFrame);
                //this.removeFromFrameToBeRecomputedFrom(i + 1);
                return;
            } else if (this.bboxes[i].frameNumber > bbox.frameNumber) {
                this.bboxes.splice(i, 0, bbox);
                this.removeFromFrameToBeRecomputedFrom(i + 1);
                this.injectInvisibleFrameAtOrigin();
                return;
            }
        }
        this.bboxes.push(bbox);
        this.injectInvisibleFrameAtOrigin();
    }

    updateBBox(bbox) {
        // console.log("updateBBox",bbox);
        for (let i = 0; i < this.bboxes.length; i++) {
            if (this.bboxes[i].frameNumber === bbox.frameNumber) {
                bbox.idBoundingBox = this.bboxes[i].idBoundingBox;
                this.bboxes[i] = bbox;
                // console.log(i, annotatedFrame);
                this.removeFromFrameToBeRecomputedFrom(i + 1);
                return;
            }
        }
    }

    removeFromFrameToBeRecomputedFrom(bboxIndex) {
        // console.log('=========== removeFromFrameToBeRecomputedFrom', bboxIndex, this.bboxes[bboxIndex]);
        let count = 0;
        for (let i = bboxIndex; i < this.bboxes.length; i++) {
            // if (this.bboxes[i].isGroundTruth) {
            //     break;
            // }
            count++;
        }
        if (count > 0) {
            this.bboxes.splice(bboxIndex, count);
        }
    }

    injectInvisibleFrameAtOrigin() {
        if (this.bboxes.length === 0 || this.bboxes[0].frameNumber > 0) {
            let bbox = new BoundingBox(0, null, null, null, null, false);
            bbox.visible = false;
            this.bboxes.splice(0, 0, bbox);
        }
    }
}
