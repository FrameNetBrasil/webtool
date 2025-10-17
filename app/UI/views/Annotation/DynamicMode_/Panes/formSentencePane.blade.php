@php
    use App\Database\Criteria;
    $origins = Criteria::table("originmm")
        ->select("idOriginMM","origin")
        ->chunkResult("idOriginMM","origin");
@endphp
<x-form title="">
    <x-slot:title>
        @if($sentence?->idSentence == 0)
            <div class="flex">
                <div class="title">Current Sentence: #new</div>
            </div>
        @else
            <div class="flex gap-2">
                <div class="title">Current Sentence: #{{$sentence->idSentence}}</div>
            </div>
        @endif
    </x-slot:title>
    <x-slot:fields>
        <x-hidden-field
            id="idDocument"
            :value="$idDocument"
        ></x-hidden-field>
        <x-hidden-field
            id="idLanguage"
            :value="$idLanguage"
        ></x-hidden-field>
        <x-hidden-field
            id="idSentence"
            :value="$sentence?->idSentence"
        ></x-hidden-field>
        <div class="fields">
            <div class="field">
                <x-text-field
                    id="startTime"
                    label="Start"
                    class="mb-2"
                    :value="$sentence?->startTime ?? 0"
                ></x-text-field>
            </div>
            <div class="field">
                <x-text-field
                    id="endTime"
                    label="End"
                    class="mb-2"
                    :value="$sentence?->endTime ?? 0"
                ></x-text-field>
            </div>
            <div class="field">
                <x-combobox.options
                    label="Text source"
                    id="idOriginMM"
                    :value="$sentence?->idOriginMM ?? 0"
                    :options="$origins"
                    class="w-14rem"
                ></x-combobox.options>
            </div>
        </div>
        <div class="field">
            <x-multiline-field
                id="text"
                label="Text"
                :value="$sentence?->text"
                class="h-4rem"
            ></x-multiline-field>
        </div>
    </x-slot:fields>
    <x-slot:buttons>
        <x-button
            type="button"
            label="Save"
            hx-post="/annotation/dynamicMode/formSentence"
        ></x-button>
        <x-button
            type="button"
            label="Reset"
            color="secondary"
            hx-get="/annotation/dynamicMode/formSentence/{{$idDocument}}/0"
            hx-target="#formSentence"
        ></x-button>
        <x-button
            type="button"
            label="Split/Delete"
            color="danger"
            x-data @click="$store.doStore.split({{$sentence?->idSentence}})"
        ></x-button>
    </x-slot:buttons>
</x-form>
