<div
    class="ui card option-card {{$color}}"
    hx-get="/report/frame/{{$frame['id']}}"
    hx-target=".report"
    hx-on::before-request="$.tab('change tab','report')"
    style="cursor: pointer;"
>
    <div class="content">
        <div class="header">
            @if($frame['namespace']->name == 'Microframe')
                <x-ui::icon.microframe/>
            @else
                <x-ui::icon.frame_ns :frame="$frame"/>
            @endif
            <span class="color_{{$frame['idColor']}}">{{$frame['name']}}</span>
        </div>
    </div>
</div>
