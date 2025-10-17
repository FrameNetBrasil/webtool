{{--
    Lexical Units Card - Organized display of lexical units by POS
    Parameters:
    - $lus: Lexical units array grouped by POS
--}}
<div class="ui card fluid data-card lexical-units-card">
    <div class="content">
        <div class="description">
            @if(count($lus) > 0)
                <div class="lexical-units-container">
                    @foreach($lus as $POS => $posLU)
                        <div class="pos-section" title="POS: {{ $POS }}">
                            <div class="pos-header">
                                <h4 class="ui header small">
                                    {{ $POS }} 
                                    <div class="sub header">({{ count($posLU) }} units)</div>
                                </h4>
                            </div>
                            <div class="pos-lexical-units">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($posLU as $lu)
                                        <button
                                            id="btnLU{{ $lu->idLU }}"
                                            class="ui button basic lu-btn"
                                        >
                                            <a href="/report/lu/{{ $lu->idLU }}">{{ $lu->name }}</a>
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
                        <div class="header">No Lexical Units</div>
                        <p>This frame does not have any documented lexical units.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>