<div class="ui equal width grid h-full">
    <div class="column h-full" style="overflow:auto">
        <div
            hx-trigger="load"
            hx-target="this"
            hx-swap="outerHTML"
            hx-get="/frame/{{$idFrame}}/classification/formFramalDomain"
        ></div>
    </div>
    <div class="column">
        <div
            hx-trigger="load"
            hx-target="this"
            hx-swap="outerHTML"
            hx-get="/frame/{{$idFrame}}/classification/formFramalType"
        ></div>
    </div>
</div>


