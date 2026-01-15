{{--
    Reusable FE Card Component
    Parameters:
    - $feObj: Frame Element object
    - $semanticTypes: Array of semantic types (optional)
    - $feType: Type of FE for styling ('core', 'peripheral', 'extra-thematic')
--}}
<div class="ui card fluid data-card {{ $feType ?? '' }}-fe"
     data-entity-id="{{ $feObj->idFrameElement }}">
    <div class="content">
        <x-ui::element.fe
            name="{{ $feObj->name }}"
            type="{{ $feType === 'core' ? 'cty_core' : 'cty_core' }}"
            :idColor="$feObj->idColor" />
    </div>
    <div class="content">
        <div class="description">
            {!! $feObj->description !!}
        </div>
    </div>

    @if($feObj->relations || isset($semanticTypes[$feObj->idFrameElement]))
        <div class="extra content" id="fe-{{ $feObj->idFrameElement }}-details" style="display: block;">
            @if($feObj->relations)
                <div class="data-card-info mb-3">
                    @foreach($feObj->relations as $relation)
                        <span class="ui label basic">
                            <b>{{ $relation['name'] }}:</b>&nbsp;{{ $relation['relatedFEName'] }}
                        </span>
                    @endforeach
                </div>
            @endif

            @if(isset($semanticTypes[$feObj->idFrameElement]))
                <div class="data-card-tags">
                    <div class="ui label semantictype">
                        {{ $semanticTypes[$feObj->idFrameElement]->name ?? '' }}
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
