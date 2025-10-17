@if($href == '')
    <span {{$attributes->merge(['class' => 'wt-inline-button'])}} {{$attributes}}>
        @if($icon != '')
            <span class="material-icons-outlined wt-button-icon wt-icon-{{$icon}}"></span>
        @endif
        <span>{{$label}}</span>
    </span>
@else
    <a href="{{$href}}" {{$attributes->merge(['class' => 'wt-inline-button'])}} {{$attributes}}>
        @if($icon != '')
            <span class="material-icons-outlined wt-button-icon wt-icon-{{$icon}}"></span>
        @endif
        {!! $label !!}
   </a>
@endif
