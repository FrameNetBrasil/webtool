<x-form
    title="Edit sentence"
    hx-put="/sentence"
>
    <x-slot:fields>
        <x-hidden-field
            id="idSentence"
            :value="$sentence->idSentence"
        ></x-hidden-field>
        @if($hasAS)
            <div class="field">
                {{$sentence->text}}
            </div>
            <div class="ui negative message">
                <i class="close icon"></i>
                <div class="header">
                    This sentence has annotations. It can't be edited.
                </div>
                <p>To edit it, remove the annotations.</p>
            </div>
        @else
        <div class="field">
            <x-multiline-field
                label="Text"
                id="text"
                :value="$sentence->text"
            ></x-multiline-field>
        </div>
        @endif
    </x-slot:fields>
    <x-slot:buttons>
        @if(!$hasAS)
        <x-submit label="Save"></x-submit>
        @endif
    </x-slot:buttons>
</x-form>
