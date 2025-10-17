<div class="search-container">
    <div class="search-input-section"
         x-data="searchFormComponent()"
         @htmx:before-request="onSearchStart"
         @htmx:after-request="onSearchComplete"
         @htmx:after-swap="onResultsUpdated"
    >
        <div class="search-input-group">
            <form class="ui form"
                  hx-post="/annotation/image/object/search"
                  hx-target=".search-result-section"
                  hx-swap="innerHTML"
            >
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <input type="hidden" name="idDocument" value="{{ $idDocument ?? 0 }}"/>
                <input type="hidden" name="annotationType" value="{{ $annotationType }}"/>
                <div class="three fields">
                    <div class="field">
                        <div class="ui left icon input">
                            <i class="search icon"></i>
                            <input
                                type="search"
                                name="frame"
                                placeholder="Search Frame/FE"
                                autocomplete="off"
                            >
                        </div>
                    </div>
                    <div class="field">
                        <div class="ui left icon input">
                            <i class="search icon"></i>
                            <input
                                type="search"
                                name="lu"
                                placeholder="Search Framed Entity"
                                autocomplete="off"
                            >
                        </div>
                    </div>
                    <div class="field">
                        <div class="ui left icon input">
                            <i class="search icon"></i>
                            <input
                                type="search"
                                name="idObject"
                                placeholder="Search #idObject"
                                autocomplete="off"
                            >
                        </div>
                    </div>
                    <div class="field button-field">
                        <button type="submit" class="ui icon button">
                            <i class="search icon"></i>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <div class="search-result-section">
        @fragment("search")
            @if(count($objects) > 0)
                <div class="search-result-data">
                    <div class="card-container">
                        <div
                            class="search-results-grid card-grid dense"
                        >
                            @foreach($objects as $object)
                                @php
                                    $status = "none";
                                    if (!is_null($object->fe)) {
                                        $status = !is_null($object->lu) ? "complete" : "partial";
                                    } else {
                                        $status = !is_null($object->lu) ? "partial" : "none";
                                    }
                                @endphp
                                <div class="ui card fluid result-card cursor-pointer {{$status}}"
                                     hx-get="/annotation/image/object"
                                     hx-target="#formsPane"
                                     hx-swap="innerHTML"
                                     hx-on::config-request="event.detail.parameters.append('idObject', this.dataset.id);event.detail.parameters.append('annotationType', '{{$annotationType}}');"
                                     tabindex="0"
                                     data-id="{{$object->idObject}}"
                                     role="button">
                                    <div
                                        class="content"
                                        data-id="{{$object->idObject}}"
                                    >
                                            <span class="right floated">
                                                <x-ui::delete
                                                    title="delete Object"
                                                    onclick="messenger.confirmDelete(`Removing Object '#{{$object->idObject}}'.`, '/annotation/staticBBox/{{$idDocument}}/{{$object->idObject}}')"
                                                ></x-ui::delete>
                                            </span>
                                        <div
                                            class="header"
                                            data-id="{{$object->idObject}}"
                                        >
                                            #{{$object->order}} [#{{$object->idObject}}]
                                        </div>
                                        <div
                                            class="meta"
                                            data-id="{{$object->idObject}}"
                                        >
                                            @if($object->fe)
                                                <x-element::frame
                                                    name="{{$object->fe->frameName}}.{{$object->fe->name}}"></x-element::frame>
                                            @endif
                                            @if($object->lu)
                                                <x-element::lu
                                                    name="{{$object->lu->frameName}}.{{$object->lu->name}}"></x-element::lu>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                </div>
            @else
                <div class="search-result-empty" id="emptyState">
                    <i class="search icon empty-icon"></i>
                    <h3 class="empty-title">No results found.</h3>
                    <p class="empty-description">
                        Enter your search above to find objects.
                    </p>
                </div>
            @endif
        @endfragment
    </div>
</div>
