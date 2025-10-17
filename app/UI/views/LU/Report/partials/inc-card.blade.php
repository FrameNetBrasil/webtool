{{--
    INC Card - incorporated FE information

    Parameters:
    - $lu: LU object with senseDescription
    - $incorporatedFE: Incorporated FE object (optional)
--}}

<div class="section-header">
    <h2 class="ui header section-title" id="incfe">
        <a href="#incfe">Incorporated FE</a>
    </h2>
</div>
<div class="section-content" id="definition-content">
    <div class="ui card fluid data-card definition-card">
        <div class="content">
            <div class="incorporated-fe-section">
                <div class="incorporated-fe-element">
                    <div class="flex-grow-1">
                        <x-element::fe name="{{$incorporatedFE->name}}"
                                       type="{{$incorporatedFE->coreType}}"
                                       idColor="{{$incorporatedFE->idColor}}">
                        </x-element::fe>
                    </div>
                    <div>
                        {{$incorporatedFE->description}}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
