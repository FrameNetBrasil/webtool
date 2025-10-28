<div class="w-20rem">
    <div class="form-field field" style="overflow:initial">
        <label for="{{$id}}">{{$label}}</label>
        <div id="{{$id}}_dropdown" class="ui tiny selection dropdown" style="overflow:initial">
            <input type="hidden" name="{{$id}}" value="{{$value}}">
            <i class="dropdown icon"></i>
            <div class="default text"></div>
            <div class="menu">
                @foreach($options as $group)
                        <div data-value="{{$group['id']}}"
                             class="item p-1 min-h-0"
                        >
                            {{$group['text']}}
                        </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
<script>
    // Initialize immediately instead of waiting for document ready
    // This ensures it works when loaded via HTMX
    $('#{{$id}}_dropdown').dropdown({
        onChange: function(value, text, $choice) {
            $('#{{$id}}').val($(value).data('value'));
        }
    });
</script>


