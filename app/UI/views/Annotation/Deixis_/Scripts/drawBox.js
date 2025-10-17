annotation.drawBox = {
    canvas: document.getElementById("canvas"),
    ctx: canvas.getContext("2d"),
    color: vatic.getColor(0),
    prevStartX: 0,
    prevStartY: 0,
    prevWidth: 0,
    prevHeight: 0,
    box: {
        x: 0,
        y: 0,
        width: 0,
        height: 0
    },
    config(config) {
        let video = document.getElementById(config.idVideoDOMElement);
        const rect= video.getBoundingClientRect();
        let $canvas = document.querySelector('#canvas');
        annotation.drawBox.offsetX = rect.x;
        annotation.drawBox.offsetY = rect.y;
        $canvas.width = annotation.video.originalDimensions.width;
        $canvas.height = annotation.video.originalDimensions.height;
        $canvas.style.position = 'absolute';
        $canvas.style.top = '0px';
        $canvas.style.left = '0px';
        $canvas.style.backgroundColor = "transparent";
        $canvas.style.zIndex = 1;
        // annotation.drawBox.scrollX = $canvas.scrollLeft();
        // annotation.drawBox.scrollY = $canvas.scrollTop();
    },
    init() {
        annotation.drawBox.isDown = false;
    },
    handleMouseDown(e) {
        e.preventDefault();
        e.stopPropagation();

        // save the starting x/y of the rectangle
        annotation.drawBox.startX = parseInt(e.clientX - annotation.drawBox.offsetX);
        annotation.drawBox.startY = parseInt(e.clientY - annotation.drawBox.offsetY);

        // set a flag indicating the drag has begun
        annotation.drawBox.isDown = true;
        // console.log('offset', annotation.drawBox.offsetX, annotation.drawBox.offsetY);
        // console.log('client', e.clientX, e.clientY);
        // console.log('down', annotation.drawBox.startX, annotation.drawBox.startY);
    },
    handleMouseUp(e) {
        e.preventDefault();
        e.stopPropagation();
        annotation.drawBox.isDown = false;

        if ((annotation.drawBox.prevWidth !== 0) && (annotation.drawBox.prevHeight !== 0)) {
            // the drag is over, clear the dragging flag
            console.log('up', annotation.drawBox.prevStartX, annotation.drawBox.prevStartY, annotation.drawBox.prevWidth, annotation.drawBox.prevHeight);
            //console.log(annotation.drawBox.ctx);
            //annotation.drawBox.ctx.strokeRect(annotation.drawBox.prevStartX, annotation.drawBox.prevStartY, annotation.drawBox.prevWidth, annotation.drawBox.prevHeight);

            // clear the canvas
            annotation.drawBox.ctx.clearRect(0, 0, annotation.drawBox.canvas.width, annotation.drawBox.canvas.height);

            Alpine.store('doStore').newObjectState = 'created';
            Alpine.store('doStore').currentVideoState = 'paused';
        }
    },
    handleMouseOut(e) {
        e.preventDefault();
        e.stopPropagation();

        // the drag is over, clear the dragging flag
        annotation.drawBox.isDown = false;
        // console.log('out');
    },
    handleMouseMove(e) {
        e.preventDefault();
        e.stopPropagation();

        // if we're not dragging, just return
        if (!annotation.drawBox.isDown) {
            // console.log('not dragging');
            return;
        }

        // get the current mouse position
        annotation.drawBox.mouseX = parseInt(e.clientX - annotation.drawBox.offsetX);
        annotation.drawBox.mouseY = parseInt(e.clientY - annotation.drawBox.offsetY);

        //annotation.drawBox.mouseX = parseInt(e.clientX);
        //annotation.drawBox.mouseY = parseInt(e.clientY);

        // Put your mousemove stuff here


        // calculate the rectangle width/height based
        // on starting vs current mouse position
        var width = annotation.drawBox.mouseX - annotation.drawBox.startX;
        var height = annotation.drawBox.mouseY - annotation.drawBox.startY;

        // clear the canvas
        annotation.drawBox.ctx.clearRect(0, 0, annotation.drawBox.canvas.width, annotation.drawBox.canvas.height);

        // draw a new rect from the start position
        // to the current mouse position
        annotation.drawBox.ctx.strokeStyle = annotation.drawBox.color.bg;
        annotation.drawBox.ctx.strokeRect(annotation.drawBox.startX, annotation.drawBox.startY, width, height);

        annotation.drawBox.prevStartX = annotation.drawBox.startX;
        annotation.drawBox.prevStartY = annotation.drawBox.startY;

        annotation.drawBox.prevWidth = width;
        annotation.drawBox.prevHeight = height;

        annotation.drawBox.box = {
            x: annotation.drawBox.startX,
            y: annotation.drawBox.startY,
            width: width,
            height: height
        }
    }
};
