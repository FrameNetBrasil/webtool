<div
    hx-trigger="load"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/image/{{$image->idImage}}/dataset/formNew"
></div>
<div
    hx-trigger="load"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/image/{{$image->idImage}}/dataset/grid"
></div>

