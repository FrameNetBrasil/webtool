<label for="{{$id}}">{{$label}}</label>
<div id="{{$id}}_search" class="ui very short search">
    <div class="ui left icon small input">
        <input type="hidden" id="{{$id}}" name="{{$id}}" value="{{$value}}">
        <input id="{{$id}}_input" {{$attributes->class(['prompt'])}} type="search" placeholder="{{$placeholder}}"
               value="{{$name}}">
        <i class="search icon"></i>
    </div>
    <div {{$attributes->class(['results'])}}></div>
</div>
<script>
    $(function() {
        $.fn.search.settings.templates.luType = function(response) {
            console.log(response);
            let html = "";
            for (lu of response.results) {
                html = html + `<div class="result"><span class="color_frame">${lu.frameName}</span>.${lu.name}</span></div>`;
            }
            return html;
        };
        $('#{{$id}}_search')
            .search({
                apiSettings: {
                    url: "/lu/list/forSelect?q={query}"
                },
                type: "luType",
                fields: {
                    title: "name"
                },
                displayField: "name",
                maxResults: 20,
                minCharacters: 3,
                onSelect: (result) => {
                    $('#{{$id}}').val(result.idLU);
                    $('#{{$id}}_input').val(`${result.frameName}.${result.name}`);
                }
            })
        ;
    });
</script>
