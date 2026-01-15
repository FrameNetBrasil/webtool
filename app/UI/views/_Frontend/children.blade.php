{{--Layout for edit children of an object in relations 1:N --}}
{{--Goal: Template for CRUD operations for children objects --}}
<div
    hx-trigger="load"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/url/for/object/{{$id}}/children/formNew"
></div>
<div
    hx-trigger="load"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/url/for/object/{{$id}}/children/grid"
></div>
