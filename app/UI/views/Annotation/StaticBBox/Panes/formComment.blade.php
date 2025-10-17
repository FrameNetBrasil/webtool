<div class="form" style="height:260px">
    <x-form
        hx-post="/annotation/staticBBox/updateObjectComment"
    >
        <x-slot:title>
            @if($order == 0)
                <div class="flex">
                    <div class="title">Comment for Object: #none</div>
                </div>
            @else
                <div class="flex gap-2">
                    <div class="title">Comment for Object: #{{$order}}</div>
                    @if($object->email)
                        <div class="text-sm">Created by [{{$object->email}}] at [{{$object->createdAt}}]</div>
                    @endif
                </div>
            @endif
        </x-slot:title>
        <x-slot:fields>
            <x-hidden-field id="idDocument" value="{{$idDocument}}"></x-hidden-field>
            <x-hidden-field id="idStaticObject" value="{{$object?->idStaticObject}}"></x-hidden-field>
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
                onclick="annotation.objects.deleteObjectComment({{$object?->idStaticObject}})"
            ></x-button>
        </x-slot:buttons>
    </x-form>
</div>

