<label for="{{$id}}">{{$label}}</label>
<div id="{{$id}}_dropdown" class="ui mini clearable selection dropdown" style="overflow:initial;">
    <input type="hidden" id="{{$id}}" name="{{$id}}" value="{{$value}}">
    <i class="dropdown icon"></i>
    <div class="default text">Select Color</div>
    <div class="menu">
        @foreach($options as $option)
            <div
                data-value="{{$option['id']}}"
                class="item {{$option['color']}}"
            ><div class="{{$option['color']}} cursor-pointer">{{$option['text']}}</div>
            </div>
        @endforeach
    </div>
</div>
<script>
    $(function() {
        $('#{{$id}}_dropdown').dropdown();
    });
</script>
