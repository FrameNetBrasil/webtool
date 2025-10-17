/**
 * Represents a bounding box at a particular frame.
 */
class Frame {
    constructor(frameNumber, bbox, isGroundTruth, idBoundingBox) {
        this.frameNumber = frameNumber;
        this.bbox = bbox;
        this.isGroundTruth = isGroundTruth;
        this.blocked = false;
        this.idBoundingBox = idBoundingBox;
    }

    isVisible() {
        return this.bbox != null;
    }
}
