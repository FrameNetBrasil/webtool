<label for="{{$id}}">{{$label}}</label>
<div id="{{$id}}_dropdown" class="ui tiny clearable selection dropdown" style="overflow:initial">
    <input type="hidden" name="{{$id}}" value="-1">
    <i class="dropdown icon"></i>
    <div class="default text"></div>
    <div class="menu">
        @foreach($options as $category => $relations)
            <div class="divider"></div>
            <div class="header color_qla_{{$category}}">
                {{$category}}
            </div>
            @foreach($relations as $r)
                <div data-value="{{$r['idQualia']}}"
                     class="item p-1 min-h-0 text-sm "
                >
                    <span class="color_qla_{{$category}}">{{$r['name']}}</span> [{{$r['frame']}}]
                </div>
            @endforeach
        @endforeach
    </div>
</div>
<script>
    $(function() {
        $('#{{$id}}_dropdown').dropdown({
            onChange: function(value, text, $choice) {
                $('#{{$id}}').val($(value).data("value"));
            }
        });
    });
</script>
