<label for="{{$id}}">{{$label}}</label>
<div id="{{$id}}_dropdown" class="ui mini clearable selection dropdown" style="overflow:initial;">
    <input type="hidden" id="{{$id}}" name="{{$id}}" value="{{$value}}">
    <i class="dropdown icon"></i>
    <div class="default text">Select Coreness</div>
    <div class="menu">
        @foreach($options as $entry => $coreType)
            <div
                data-value="{{$entry}}"
                class="item"
            >{{$coreType}}
            </div>
        @endforeach
    </div>
</div>
<script>
    $(function() {
        $('#{{$id}}_dropdown').dropdown();
    });
</script>
