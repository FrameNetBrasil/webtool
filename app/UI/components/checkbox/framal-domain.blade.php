@if($label != '')
    <label for="{{$id}}">{{$label}}</label>
@endif
<div class="ui form">
    <div class="grouped fields">
        @foreach($options as $i => $option)
            <div class="field">
                <div class="ui checkbox {{$option['checked']}}">
                    <input type="checkbox" name="{{$id}}[{{$i}}]" value="{{$option['value']}}" {{$option['checked']}}>
                    <label>{{$option['name']}}</label>
                </div>
            </div>
        @endforeach
    </div>
</div>
<script>
    $(function() {
        $(".ui.checkbox")
            .checkbox()
        ;
    });
</script>
