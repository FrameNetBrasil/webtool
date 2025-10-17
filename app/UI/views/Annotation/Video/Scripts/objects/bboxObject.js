/**
 * Represents a bounding box at a particular frame.
 */
class BoundingBox {
    constructor(frameNumber, x, y, width, height, isGroundTruth, blocked, idBoundingBox) {
        this.frameNumber = frameNumber;
        this.x = x;
        this.y = y;
        this.width = width;
        this.height = height;
        this.isGroundTruth = isGroundTruth;
        this.blocked = blocked;
        this.idBoundingBox = idBoundingBox;
        this.visible = true;
    }

    isVisible() {
        //return this.bbox != null;
        return this.visible;
    }
}
