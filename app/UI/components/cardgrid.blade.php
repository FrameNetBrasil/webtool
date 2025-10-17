<div {{$attributes->class(["wt-cardgrid","wt-container-center-content" => $center])}} >
    @if($title != '')
    <div class="cardgrid-header">
        <div class="cardgrid-title">{{$title}}{!! $extraTitle !!}</div>
    </div>
    @endif
    <div class="cardgrid-body">
        <div class="grid">
            {{$slot}}
        </div>
    </div>
</div>
