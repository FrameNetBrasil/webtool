@if($label !='')
<label for="{{$id}}">{{$label}}</label>
@endif
<div id="{{$id}}_dropdown" class="ui tiny clearable selection dropdown" style="overflow:initial">
    <input type="hidden" name="{{$id}}" value="-1">
    <i class="dropdown icon"></i>
    <div class="default text">{{$value}}</div>
    <div class="menu">
        @foreach($options as $lu)
            <div data-value="{{$lu['idLU']}}"
                 class="item p-1 min-h-0">
                <x-element.lu name="{{$lu['name']}}"></x-element.lu>
            </div>
        @endforeach
    </div>
</div>
<script>
    $(function() {
        $('#{{$id}}_dropdown').dropdown();
    });
</script>
