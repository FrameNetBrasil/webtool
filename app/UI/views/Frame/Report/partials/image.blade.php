@php
    $imageWidth = intval($image->width);
    $imageHeight = intval($image->height);
@endphp
<div class="d-flex p-2">
    @foreach($fes as $fe)
        <div style="height:16px;width:16px;background-color:#{{$fe[0]->color}};margin-right:2px"></div>
        <div class="mr-3">{{$fe[0]->fe}}</div>
    @endforeach
</div>
<div class="flexible-image-container">
    <div class="image-wrapper" style="position:relative;">
        <img
                id="imageStaticBBox"
                src="{!! config('webtool.mediaURL') . "/" . $image->currentURL !!}"
                data-width="{{$imageWidth}}"
                data-height="{{$imageHeight}}"
                style="max-width: 100%; height: auto; display: block;"
        />
        <canvas
                id="canvas"
                width=0
                height=0
                style="position: absolute; top: 0; left: 0; pointer-events: none;"
        ></canvas>
        <div id="boxesContainer" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></div>
    </div>
</div>
<script>
    function updateBoundingBoxes() {
        const img = document.getElementById('imageStaticBBox');
        const container = document.getElementById('boxesContainer');
        
        if (!img || !container) return;
        
        // Clear existing boxes
        container.innerHTML = '';
        
        // Get the actual displayed image dimensions
        const displayedWidth = img.offsetWidth;
        const displayedHeight = img.offsetHeight;
        
        // Get the original image dimensions
        const originalWidth = parseInt(img.dataset.width);
        const originalHeight = parseInt(img.dataset.height);
        
        // Calculate scale factors
        const scaleX = displayedWidth / originalWidth;
        const scaleY = displayedHeight / originalHeight;
        
        // Create bounding boxes with scaled dimensions
        @foreach($bboxes as $bbox)
        const bbox{{$loop->index}} = document.createElement("div");
        bbox{{$loop->index}}.className = "bbox";
        bbox{{$loop->index}}.style.position = "absolute";
        bbox{{$loop->index}}.style.display = "block";
        bbox{{$loop->index}}.style.width = Math.round({{$bbox->width}} * scaleX) + "px";
        bbox{{$loop->index}}.style.height = Math.round({{$bbox->height}} * scaleY) + "px";
        bbox{{$loop->index}}.style.left = Math.round({{$bbox->x}} * scaleX) + "px";
        bbox{{$loop->index}}.style.top = Math.round({{$bbox->y}} * scaleY) + "px";
        bbox{{$loop->index}}.style.borderColor = "#{{$fes[$bbox->idStaticObject][0]->color}}";
        bbox{{$loop->index}}.style.borderStyle = "solid";
        bbox{{$loop->index}}.style.borderWidth = "2px";
        bbox{{$loop->index}}.style.backgroundColor = "transparent";
        bbox{{$loop->index}}.style.opacity = 1;
        container.appendChild(bbox{{$loop->index}});
        @endforeach
    }
    
    // Update bounding boxes when image loads
    document.getElementById('imageStaticBBox').addEventListener('load', updateBoundingBoxes);
    
    // Update bounding boxes on window resize
    window.addEventListener('resize', updateBoundingBoxes);
    
    // Initial update
    setTimeout(updateBoundingBoxes, 100);
</script>
