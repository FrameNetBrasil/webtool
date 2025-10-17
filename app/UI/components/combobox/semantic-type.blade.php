<label for="{{$id}}">{{$label}}</label>
<div class="wt-combotreegrid">
<input {{$attributes}} type="search" id="{{$id}}" name="{{$id}}">
</div>
<script>
    $(function() {
        $('#{{$id}}').combotreegrid({
            width: "100%",
            data: {{ Js::from($list) }},
            idField: "idSemanticType",
            treeField: "html",
            textField: "name",
            showHeader: false,
            columns: [[{
                field: "html",
                title: "Name",
                width: "100%"
            }
            ]]
        });
        $('#{{$id}}').combotreegrid("grid").treegrid("getPanel").addClass("wt-combotreegrid-panel");
    });
</script>
