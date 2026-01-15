{{--
    Frame Relations Card - Organized display of frame-to-frame relations
    Parameters:
    - $relations: Relations array grouped by type
--}}

<div class="ui card fluid data-card relations-card">
    <div class="content">
        <div class="description">
            @if(count($asRestriction) > 0)
                <div class="relations-container">
                    @foreach($asRestriction as $direction => $relations1)
                        @foreach($relations1 as $nameEntry => $relations2)
                            @php([$entry, $name] = explode('|', $nameEntry))
                            @php($relId = str_replace(' ', '_', $name))
                            <div class="ui segment">
                                <div class="ui two column very relaxed grid">
{{--                                    @if ($direction == 'direct')--}}
{{--                                    <div class="column w-1/4">--}}
{{--                                        <h4 class="ui header small">{{$frame->name}} <span class="color_frame">{{ $name }}</span></h4>--}}
{{--                                    </div>--}}
{{--                                    <div class="column w-3/4">--}}
{{--                                        @foreach($relations2 as $idFrame => $relation)--}}
{{--                                            <button--}}
{{--                                                id="btnRelation_{{ $relId }}_{{ $idFrame }}"--}}
{{--                                                class="ui button basic relation-frame-btn mb-1"--}}
{{--                                            >--}}
{{--                                                <a href="/report/class/{{ $idFrame }}">--}}
{{--                                                    <x-ui::element.frame--}}
{{--                                                        name="{{ $relation['name'] }}"></x-ui::element.frame>--}}
{{--                                                </a>--}}
{{--                                            </button>--}}
{{--                                        @endforeach--}}
{{--                                    </div>--}}
{{--                                    @endif--}}
{{--                                        @if ($direction == 'inverse')--}}
                                            <div class="column w-3/4">
                                                @foreach($relations2 as $idFrame => $relation)
                                                    <button
                                                        id="btnRelation_{{ $relId }}_{{ $idFrame }}"
                                                        class="ui button basic relation-frame-btn mb-1"
                                                    >
                                                        <a href="/report/class/{{ $idFrame }}">
                                                            <x-ui::element.frame
                                                                name="{{ $relation['name'] }}"></x-ui::element.frame>
                                                        </a>
                                                    </button>
                                                @endforeach
                                            </div>
                                            <div class="column w-1/4">
                                                <h4 class="ui header small"><span class="color_frame">{{ $name }}</span> {{$frame->name}} </h4>
                                            </div>
{{--                                        @endif--}}
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="ui message info">
                        <div class="header">No Relations</div>
                        <p>This frame does not have any documented frame-to-frame relations.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
