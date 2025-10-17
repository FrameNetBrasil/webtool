<div
    hx-trigger="load"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/sentence/{{$sentence->idSentence}}/document/formNew"
></div>
<div
    hx-trigger="load"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/sentence/{{$sentence->idSentence}}/document/grid"
></div>

