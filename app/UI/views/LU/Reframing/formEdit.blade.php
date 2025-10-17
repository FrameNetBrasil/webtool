@if($alreadyExists)
<div class="ui visible warning message mt-2">
    LU already exists in the target frame. Reframing will not update sense description or incorporatedFE.
</div>
@endif

<x-form>
    <x-slot:fields>
        <x-hidden-field id="idLU" :value="$lu->idLU"></x-hidden-field>
        <x-hidden-field id="idNewFrame" :value="$idNewFrame"></x-hidden-field>
        <div class="field">
            <x-multiline-field
                label="Sense Description"
                id="senseDescription"
                :value="$lu->senseDescription"
            ></x-multiline-field>
        </div>
        <div class="field">
            <x-combobox.fe-frame
                id="incorporatedFE"
                label="Incorporated FE"
                value=""
                :idFrame="$idNewFrame"
                :hasNull="true"
            ></x-combobox.fe-frame>
        </div>
        <div
            class="field h-full"
            hx-trigger="load"
            hx-get="/reframing/fes/{{$lu->idLU}}/{{$idNewFrame}}"
        >
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit label="Reframing" hx-put="/reframing"></x-submit>
    </x-slot:buttons>
</x-form>
