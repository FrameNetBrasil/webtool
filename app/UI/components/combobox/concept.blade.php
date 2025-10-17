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
                    url: "/concept/list/forSelect?q={query}"
                },
                fields: {
                    title: "name",
                    description: "{{$description}}"
                },
                maxResults: 20,
                minCharacters: 3,
                onSelect: (result) => {
                    $('#{{$id}}').val(result.idConcept);
                    {!! $onSelect !!}
                ;}
            })
        ;
    });
</script>
