
<div class="ui breadcrumb">
    @foreach($sections as $section)
        @if ($loop->last)
            <div class="active section">{{$section[1]}}</div>
        @else
            <a href="{{$section[0]}}" class="section" hx-boost="true">{{$section[1]}}</a>
            <i class="right chevron icon divider"></i>
        @endif
    @endforeach
</div>
