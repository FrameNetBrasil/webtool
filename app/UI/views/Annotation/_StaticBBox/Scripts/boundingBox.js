/**
 * BoundingBox
 */

"use strict";

class BoundingBox {
    constructor(x, y, width, height) {
        console.log('bounding box constructor',x, y, width, height);
        this.x = parseInt(x);
        this.y = parseInt(y);
        this.width = parseInt(width);
        this.height = parseInt(height);
        this.blocked = 0;
    }
}
