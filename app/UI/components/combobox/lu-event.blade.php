<div class="form-field field" {{$width}} {!! $width ? "style='width:{$width}'" : "" !!}>
    <label for="{{$id}}">{{$label}}</label>
    <div id="{{$id}}_search" class="ui very short search">
        <div class="ui left icon small input">
            <input type="hidden" id="{{$id}}" name="{{$id}}" value="">
            <input class="prompt" type="search" placeholder="{{$placeholder}}">
            <i class="search icon"></i>
        </div>
        <div class="results"></div>
    </div>
</div>
<script>
    $(function() {
        $('#{{$id}}_search')
            .search({
                apiSettings: {
                    url: "/lu/list/forEvent?q={query}"
                },
                fields: {
                    title: "name"
                },
                displayField: 'name',
                maxResults: 20,
                minCharacters: 3,
                onSelect: (result) => {
                    console.log(result);
                    $('#{{$id}}').val(result.idLU);
                }
            })
        ;
    });
</script>
