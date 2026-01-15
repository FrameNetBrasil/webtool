<div class="search-container">
    <div class="search-input-section"
         x-data="searchObjectComponent()"
         @htmx:before-request="onSearchStart"
         @htmx:after-request="onSearchComplete"
         @htmx:after-swap="onResultsUpdated"
         @timeline-seek-frame.document="onSeekObject"
    >
        <div class="search-input-group">
            <form class="ui form"
                  hx-post="/annotation/video/object/search"
                  hx-target=".search-result-section"
                  hx-swap="innerHTML">
                <input type="hidden" name="_token" value="{{ csrf_token() }}"/>
                <input type="hidden" name="idDocument" value="{{ $idDocument ?? 0 }}"/>
                <input type="hidden" name="annotationType" value="{{ $annotationType }}"/>
                <input type="hidden" name="frameNumber" x-model="frameInput"/>
                <input type="hidden" name="useFrameNumber" value="0"/>
                <div class="four fields">
                    <div class="field">
                        <div class="ui left icon input">
                            <i class="search icon"></i>
                            <input
                                type="search"
                                name="frame"
                                placeholder="Search Frame/FE"
                                x-model="searchQueryFrame"
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
                                placeholder="Search CV name"
                                x-model="searchQueryLU"
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
                                x-model="searchQueryIdDynamicObject"
                                autocomplete="off"
                            >
                        </div>
                    </div>
                    <div class="field">
                        <div class="fields">
                            <div class="field">
                                <button type="submit" class="ui icon button">
                                    <i class="search icon"></i>
                                </button>
                            </div>
                            <div class="field">
                                <button type="submit" class="ui icon button">
{{--                                    <i class="ui icon material">full_stacked_bar_chart</i>--}}
                                    All
                                </button>
                            </div>
                            <div class="field">
                                <button
                                    type="submit"
                                    class="ui button"
                                    name="useFrameNumber"
                                    value="1"
                                >
                                    Current
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="search-result-section">
        @fragment("search")
            @if(count($searchResults) > 0)
                <div class="search-result-data">
                    <div class="search-result-header">
                        <div class="result-info">
                            <div class="result-count" id="resultsCount">{!! count($searchResults ?? []) !!}
                                results
                            </div>
                        </div>
                    </div>
                    <div class="card-container">
                        <div
                            class="search-results-grid card-grid dense"
                        >
                            @foreach($searchResults as $i => $object)
                                @php
                                    $status = "none";
                                    if (($object->fe)) {
                                        $status = ($object->lu) ? "complete" : "partial";
                                    } else {
                                        $status = ($object->lu) ? "partial" : "none";
                                    }
                                @endphp

                                <div class="ui card fluid result-card cursor-pointer {{$status}}"
                                     hx-get="/annotation/video/object"
                                     hx-target="#formsPane"
                                     hx-swap="innerHTML"
                                     hx-on::config-request="Object.assign(event.detail.parameters, {idObject: this.dataset.id, annotationType: '{{$annotationType}}', idDocument: '{{$idDocument}}'});"
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
                                                onclick="messenger.confirmDelete(`Removing Object '#{{$object->idObject}}'.`, '/annotation/{{$annotationType}}/{{$idDocument}}/{{$object->idObject}}')"
                                            ></x-ui::delete>
                                        </span>
                                        <div
                                            class="header"
                                            data-id="{{$object->idObject}}"
                                        >
                                            Object: #{{$object->idObject}}
                                        </div>
                                        <div
                                            class="meta"
                                            data-id="{{$object->idObject}}"
                                        >
                                            @if($object->fe)
                                                <x-element::frame
                                                    name="{{$object->frame}}.{{$object->fe}}"></x-element::frame>
                                            @endif
                                            @if($object->lu)
                                                <x-element::lu
                                                    name="{{$object->lu}}"></x-element::lu>
                                            @endif

                                            Frames: {{$object->startFrame}}-{{$object->endFrame}}<br/>
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
