<label>FE annotations mapping from [{{$lu->frameName}}.{{$lu->name}}] to [{{$newFrame->name}}.{{$lu->name}}]</label>
@if($countAS > 0)
    <div class="ui visible warning message">
        {{$countAS}} AnnotationSets for this LU.
    </div>
@else
    <div class="ui visible info message">
        There is no AnnotationSets for this LU.
    </div>
@endif
@foreach($fes as $i => $fe)
    <div class="grid">
        <div class="col-fixed" style="width:20rem">
            <x-hidden-field id="idEntityFE[{{$i}}]" :value="$fe->idEntity"></x-hidden-field>
            <x-element.fe
                name="{{$fe->name}}"
                type="{{$fe->coreType}}"
                idColor="{{$fe->idColor}}"
            ></x-element.fe>
        </div>
        <div class="col">
            <x-combobox.fe-frame
                id="changeToFE_{{$i}}"
                name="changeToFE[{{$i}}]"
                label="change to"
                value=""
                :idFrame="$idNewFrame"
                :hasNull="true"
            ></x-combobox.fe-frame>
        </div>
    </div>
@endforeach
