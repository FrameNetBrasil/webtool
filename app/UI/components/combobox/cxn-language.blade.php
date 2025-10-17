<div {{$attributes}}>
    <div class="form-field field" style="overflow:initial">
        <label for="{{$id}}">{{$label}}</label>
        <div id="{{$id}}_dropdown" class="ui tiny selection dropdown" style="overflow:initial">
            <input type="hidden" name="{{$id}}" value="{{$value}}">
            <i class="dropdown icon"></i>
            <div class="default text">Language</div>
            <div class="menu">
                @foreach($options as $idOption => $language)
                    <div data-value="{{$idOption}}"
                         class="item p-1 min-h-0"
                    >
                        {{$language}}
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<script>
    $(function() {
        $('#{{$id}}_dropdown').dropdown({
            onChange: function(value, text, $choice) {
                $('#{{$id}}').val($(value).data("value"));
            }
        })
    });
</script>
