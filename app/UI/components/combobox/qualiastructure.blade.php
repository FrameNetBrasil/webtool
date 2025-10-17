<div class="form-field field">
    @if($label != '')
    <label for="{{$id}}">{{$label}}</label>
    @endif
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
                    url: "/tqr2/list/forSelect?q={query}"
                },
                fields: {
                    title: "name"
                },
                maxResults: 20,
                minCharacters: 2,
                onSelect: (result) => {
                    $('#{{$id}}').val(result.idQualiaStructure);
                    @if($onSelect)
                        {!! $onSelect !!}
                    @endif
                },
                @if($onChange ?? false)
                onChange: function(value, text, $choice) {
                    {!! $onChange !!}
                }
                @endif
            })
        ;
    });
</script>
