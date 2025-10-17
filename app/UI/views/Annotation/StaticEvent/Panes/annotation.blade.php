<script type="text/javascript">
    let annotation = {
        objects: {{Js::from($objects)}},
        frames: {{Js::from($frames)}},
        image: {
            width: {{$image->width}},
            height: {{$image->height}}
        },
        newBboxElement(bbox) {
            let box = document.createElement('div');
            box.className = 'wt-anno-box-border-' + bbox.idObject;
            const image = document.getElementById("image");
            image.appendChild(box);
            let x = parseInt(bbox.x);
            let y = parseInt(bbox.y);
            let w = parseInt(bbox.width) - 0;
            let h = parseInt(bbox.height) - 0;
            box.style.width = w + 'px';
            box.style.height = h + 'px';
            box.style.left = x + 'px';
            box.style.top = y + 'px';
            box.style.position = 'absolute';
            box.style.zIndex = 3;
            let id = document.createElement('div');
            id.className = 'objectId';
            id.innerHTML = bbox.tag;
            box.appendChild(id);
        },
        drawBBoxes() {
            let newBBoxes = {};
            let idBBox = '';
            for (var idAnnotationObject in this.objects) {
                let object = this.objects[idAnnotationObject]
                console.log(object);
                let bboxes = object.bboxes;
                if (bboxes[0] === undefined) {
                    if (object.name === 'scene') {
                        if (newBBoxes[0] === undefined) {
                            newBBoxes[0] = {
                                tag: 'scene ' + object.idObject,
                                idObject: object.idObject,
                                x: 0,
                                y: 0,
                                width: this.image.width,
                                height: this.image.height
                            }
                        }
                    }
                } else {
                    for (let bbox of bboxes) {
                        idBBox = 'box_' + bbox.x + '_' + bbox.y + '_' + bbox.width + '_' + bbox.height;
                        if (newBBoxes[idBBox] === undefined) {
                            newBBoxes[idBBox] = {
                                tag: object.idObject,
                                idObject: object.idObject,
                                x: bbox.x,
                                y: bbox.y,
                                width: bbox.width,
                                height: bbox.height
                            }
                        } else {
                            newBBoxes[idBBox].tag = newBBoxes[idBBox].tag + ',' + object.idObject;
                        }
                    }
                }
            }
            console.log(newBBoxes);
            for (let idBBox in newBBoxes) {
                this.newBboxElement(newBBoxes[idBBox]);
            }
        },

    }
    $(function () {
        annotation.drawBBoxes();
    });
</script>
