{{--<div class="w-20rem">--}}
{{--    <div class="field" style="overflow:initial">--}}
        <label for="{{$id}}">Relation</label>
        <div id="{{$id}}_dropdown" class="ui tiny selection dropdown w-15em" style="overflow:initial">
            <input type="hidden" name="{{$id}}" value="-1">
            <i class="dropdown icon"></i>
            <div class="default text"></div>
            <div class="menu">
                @foreach($options as $r)
                    @if($r['value'] == 'header')
                        <div class="divider"></div>
                        <div class="header">
                            {{$r['name']}}
                        </div>
                    @else
                        <div data-value="{{$r['value']}}"
                             class="item p-1 min-h-0"
                             style="color:{{$r['color']}}"
                        >
                            {{$r['name']}}
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
{{--    </div>--}}
{{--</div>--}}
<script>
    $(function() {
        $('#{{$id}}_dropdown').dropdown({
            onChange: function(value, text, $choice) {
                $('#{{$id}}').val($(value).data('value'));
            }
        });
    });
</script>
