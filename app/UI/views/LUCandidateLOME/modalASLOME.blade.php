@if(count($asLOME) > 0)
    <script type="text/javascript" src="/annotation/corpus/script/components"></script>
    <div class="field mr-1">
        <label></label>
        <button
                type="button"
                class="ui secondary button"
{{--                hx-get="/luCandidate/{{$luCandidate->idLU}}/asLOME"--}}
{{--                hx-target="#asLOMEModal .content"--}}
{{--                hx-swap="innerHTML"--}}
                onclick="$('#asLOMEModal').modal('show')"
        >
           [{!! count($asLOME) !!}] AnnotationSet LOME
        </button>
        <div id="asLOMEModal" class="ui fullscreen modal">
            <i class="close icon"></i>
            <div class="content">
                <div class="ui form">
                    <div class="field" style="width:200px">
                        <div
                            class="ui selection dropdown"
                            x-init="$($el).dropdown({
                                    onChange: (value, text, $selectedItem) => {
                                        console.log(value, text, $selectedItem);
                                        htmx.ajax('GET','/annotation/fe/asExternal/' + value,'.annotationSetContainer');
                                    }
                                })"
                        >
                            <input type="hidden" name="idAnnotationSetLOME"
                                   id="idAnnotationSetLOME">
                            <i class="dropdown icon"></i>
                            <div class="default text">AnnotationSet LOME</div>
                            <div class="menu">
                                @foreach($asLOME as $as)
                                    <div class="item"
                                         data-value="{{$as->idAnnotationSet}}">{{$as->idAnnotationSet}}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @php
                        $idAnnotationSet = $asLOME[0]->idAnnotationSet;
                    @endphp
                    <div
                        class="annotationSetContainer"
                        hx-trigger="load"
                        hx-get="/annotation/fe/asExternal/{{$idAnnotationSet}}"
                        hx-target="this"
                        hx-swap="innerHTML"
                    ></div>
                </div>
            </div>
        </div>
    </div>
@endif

