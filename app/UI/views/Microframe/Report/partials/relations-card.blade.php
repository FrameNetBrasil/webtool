{{--
    Frame Relations Card - Organized display of frame-to-frame relations
    Parameters:
    - $relations: Relations array grouped by type
--}}
<div class="ui card fluid data-card relations-card">
    <div class="content">
        <div class="description">
            @if(count($relations) > 0)
                <div class="relations-container">
                    @foreach($relations as $nameEntry => $relations1)
                        @php([$entry, $name] = explode('|', $nameEntry))
                        @php($relId = str_replace(' ', '_', $name))
                        <div class="relation-type-section"
                             title="<span class='color_{{ $entry }}'>{{ $name }}</span>">
                            <div class="relation-type-header">
                                <h4 class="ui header small">{{ $name }}</h4>
                            </div>
                            <div class="relation-frames">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($relations1 as $idFrame => $relation)
                                        <button
                                            id="btnRelation_{{ $relId }}_{{ $idFrame }}"
                                            class="ui button basic relation-frame-btn"
                                        >
                                            <a href="/report/microframe/{{ $idFrame }}">
                                                <x-ui::element.frame name="{{ $relation['name'] }}"></x-ui::element.frame>
                                            </a>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @if(!$loop->last)
                            <div class="ui divider"></div>
                        @endif
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
