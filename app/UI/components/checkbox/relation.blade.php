<div id="{{$id}}_dropdown" class="ui small multiple selection dropdown" style="overflow:initial">
    <i class="dropdown icon"></i>
    <div class="p-1">{{$label}}</div>
    <div class="menu">
        @foreach($relations as $i => $relation)
            <div class="item" style="min-height:1.5rem;padding:4px 8px" >
                <div class="ui checkbox">
                    <input type="checkbox" name="{{$id}}[{{$i}}]" value="{{$relation->idRelationType}}" checked>
                    <label><span style="color:{{$relation->color}}">{{$relation->name}}</span></label>
                </div>
            </div>
        @endforeach
    </div>
</div>
<script>
    $(function() {
        $('#{{$id}}_dropdown').dropdown({
            collapseOnActionable: false
        });
    });
</script>
