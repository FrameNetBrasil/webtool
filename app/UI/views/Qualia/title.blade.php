<div>
    @if(isset($data->qualia))
    <span>Qualia: {{$data->qualia?->name}}</span>
    @else
    <span>Qualia</span>
    @endif
</div>
