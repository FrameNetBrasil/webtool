<div>
    <span class="font-bold"><x-element::frame_ns :frame="$frame"></x-element::frame_ns></span>
    <div class='definition'>{!! str_replace('</ex>','</div>',str_replace('<ex>','<div class="example">',$frame->description)) !!}</div>
    <div class='mt-1'>{{$frame->domain}}</div>
</div>
