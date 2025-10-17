@if($label != '')
    <label for="{{$id}}">{{$label}}</label>
@endif
<div id="{{$id}}_dropdown" class="ui tiny selection dropdown" style="overflow:initial">
    <input type="hidden" id="{{$id}}" name="{{$id}}" value="{{$value}}">
    <i class="dropdown icon"></i>
    <div class="default text"></div>
    <div class="menu">
        @foreach($options as $idOption => $option)
            <div data-value="{{$idOption}}"
                 class="item p-1 min-h-0"
            >
                {{$option->name}}
            </div>
        @endforeach
    </div>
</div>
<script>
    $(function() {
        $('#{{$id}}_dropdown').dropdown({});
    });
</script>
