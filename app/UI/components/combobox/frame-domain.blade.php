<label for="{{$id}}">{{$label}}</label>
<div id="{{$id}}_dropdown" class="ui small selection dropdown" style="overflow:initial">
    <input type="hidden" id="{{$id}}" name="{{$id}}" value="{{$value}}">
    <i class="dropdown icon"></i>
    <div class="default text"></div>
    <div class="menu">
        @foreach($options as $option)
            <div data-value="{{$option->idSemanticType}}"
                 class="item p-1 min-h-0"
            >
                {{$option->name}}
            </div>
        @endforeach
    </div>
</div>
<script>
    $(function() {
        $('#{{$id}}_dropdown').dropdown({
            onChange: function(value, text, $choice) {
                $('#{{$id}}').val(value);
            }
        });
    });
</script>
