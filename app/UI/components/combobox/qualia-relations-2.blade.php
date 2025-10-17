<div class="w-20rem">
    <div class="form-field field" style="overflow:initial">
        <label for="{{$id}}">{{$label}}</label>
        <div id="{{$id}}_dropdown" class="ui tiny clearable selection dropdown" style="overflow:initial">
            <input type="hidden" name="{{$id}}" value="-1">
            <i class="dropdown icon"></i>
            <div class="default text"></div>
            <div class="menu">
                @foreach($options as $idQualiaRelation => $relation)
                    <div data-value="{{$idQualiaRelation}}"
                         class="item p-1 min-h-0 text-sm "
                    >
                        <span>{{$relation->name}}</span>
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
                $('#{{$id}}').val($(value).data('value'));
            }
        });
    });
</script>
