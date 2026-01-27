/*
    StaticBBoxObject
 */

"use strict";

class StaticBBoxObject {
    constructor(object) {
        this.object = object;
        if (object === null) {
            // new Object
            this.idObject = 0;
            this.color = annotation.objects.colors[0];
        } else {
            this.idObject = parseInt(object.order);
            this.color = annotation.objects.colors[object.order];
        }
        this.visible = true;
        this.hidden = false;
        this.locked = false;
        this.scale = 1;
        this.dom = null;
        this.bbox = null;
    }

    getScaledBBox() {
        console.log('getScaledBBox',this.bbox,this.scale);
        let bbox = new BoundingBox(
            this.bbox.x,
            this.bbox.y,
            this.bbox.width,
            this.bbox.height
        );
        bbox.width = parseInt(bbox.width / this.scale);
        bbox.height= parseInt(bbox.height / this.scale);
        console.log(this.bbox, bbox);
        return bbox;
    }

    getBBox() {
        console.log('getBBox',this.bbox);
        let bbox = new BoundingBox(
            this.bbox.x,
            this.bbox.y,
            this.bbox.width,
            this.bbox.height
        );
        console.log(this.bbox, bbox);
        return bbox;
    }

    loadBBox(bbox) {
        if ((this.idDocument >= 2248) && (this.idDocument <= 15779))
        {
            this.bbox = new BoundingBox(
                parseInt(bbox.x * this.scale),
                parseInt(bbox.y * this.scale),
                parseInt(bbox.width * this.scale),
                parseInt(bbox.height * this.scale),
            );
        } else {
            this.bbox = new BoundingBox(
                bbox.x,
                bbox.y,
                parseInt(bbox.width * this.scale),
                parseInt(bbox.height * this.scale),
            );
        }
    }

    drawBox() {
        console.log('draw box',this.bbox);
        if (this.bbox) {
            let bbox = this.bbox;
            this.dom.style.display = "none";
            if (!this.hidden) {
                this.dom.style.position = "absolute";
                this.dom.style.display = "block";
                this.dom.style.width = bbox.width + "px";
                this.dom.style.height = bbox.height + "px";
                this.dom.style.left = bbox.x + "px";
                this.dom.style.top = bbox.y + "px";
                this.dom.style.borderColor = this.color;
                // this.dom.querySelector(".objectId").style.backgroundColor = this.color.bg;
                // this.dom.querySelector(".objectId").style.color = this.color.fg;
                this.dom.style.borderStyle = "solid";
                this.dom.style.borderWidth = "4px";
                this.dom.style.backgroundColor = "transparent";
                this.dom.style.opacity = 1;
                this.visible = true;
                if (bbox.blocked) {
                    this.dom.style.opacity = 0.5;
                    this.dom.style.backgroundColor = "white";
                    this.dom.style.borderStyle = "dashed";
                }
            }
        }
    }

}
