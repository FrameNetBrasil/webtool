<div class="form" style="height:260px">
    <x-form
        hx-post="/annotation/fullText/updateObjectComment"
    >
        <x-slot:title>
            <div class="flex gap-2">
                <div class="title">Comment for AnnotationSet #{{$object->idAnnotationSet}}</div>
                @if($object->email)
                    <div class="text-sm">Created by [{{$object->email}}] at [{{$object->createdAt}}]</div>
                @endif
            </div>
        </x-slot:title>
        <x-slot:fields>
            <x-hidden-field id="idAnnotationSet" value="{{$object->idAnnotationSet}}"></x-hidden-field>
            <x-hidden-field id="createdAt" value="{{$object?->createdAt}}"></x-hidden-field>
            <div class="field mr-1">
                <x-multiline-field
                    label="Comment"
                    id="comment"
                    :value="$object->comment ?? ''"
                ></x-multiline-field>
            </div>
        </x-slot:fields>
        <x-slot:buttons>
            <x-submit label="Save"></x-submit>
            <x-button
                type="button"
                label="Delete"
                color="danger"
                onclick="messenger.confirmDelete(`Removing Comment for #{{$object->idAnnotationSet}}'.`, '/annotation/fullText/comment/{{$object->idAnnotationSet}}', null, '')"
            ></x-button>
        </x-slot:buttons>
    </x-form>
</div>

