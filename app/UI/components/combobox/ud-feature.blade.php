@if($label != '')
    <label for="{{$id}}">{{$label}}</label>
@endif
<div id="{{$id}}_search" class="ui very short search">
    <div class="ui left icon small input">
        <input type="hidden" id="{{$id}}" name="{{$idName ?? $id}}" value="{{$value}}">
        <input class="prompt" type="search" placeholder="{{$placeholder}}" value="{{$name}}">
        <i class="search icon"></i>
    </div>
    <div class="results"></div>
</div>
<script>
    $(function() {
        $('#{{$id}}_search')
            .search({
                apiSettings: {
                    url: "/lexicon3/feature/listForSelect?q={query}"
                },
                fields: {
                    title: "name",
                },
                maxResults: 20,
                minCharacters: 1,
                onSelect: (result) => {
                    $('#{{$id}}').val(result.idUDFeature);
                    {!! $onSelect !!}
                ;}
            })
        ;
    });
</script>
