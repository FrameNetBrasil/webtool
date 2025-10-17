@if($label!='')
    <label for="{{$id}}">{{$label}}</label>
@endif
<div id="{{$id}}_dropdown" class="ui clearable selection dropdown frameElement w-15em" style="overflow:initial;">
    <input type="hidden" id="{{$id}}" name="{{$name}}" value="{{$value}}">
    <i class="dropdown icon"></i>
    <div class="default text">{{$defaultText}}</div>
    <div class="menu">
        @foreach($options as $fe)
            <div data-value="{{$fe['idFrameElement']}}"
                 class="item p-1 min-h-0">
                @if($fe['coreType'] != '')
                    <x-element.fe name="{{$fe['name']}}" type="{{$fe['coreType']}}"
                                  idColor="{{$fe['idColor']}}"></x-element.fe>
                @else
                    <span>{{$fe['name']}}</span>
                @endif
            </div>
        @endforeach
    </div>
</div>
<script>
    $(function() {
        $('#{{$id}}_dropdown').dropdown({
            @if($onChange)
            onChange: (value) => {
                {!! $onChange !!}
            }
            @endif
        });
    });
</script>
