<div
    id="formsPane"
    class="h-full"
    x-data="formsComponent({{$idDocument}})"
    @bbox-drawn.document="onBBoxDrawn"
    @bbox-update.document="onBBoxUpdate"
>
    @if ($idObject == 0)
        @include("Annotation.Image.Forms.formNewObject")
    @else
        <div
            hx-trigger="load"
            hx-target="#formsPane"
            hx-get="/annotation/image/object"
            hx-vals='{"idObject": {{$idObject}},"idDocument": {{$idDocument}}, "annotationType":"{{$annotationType}}" }'
            hx-swap="innerHTML"
        ></div>
    @endif
</div>

