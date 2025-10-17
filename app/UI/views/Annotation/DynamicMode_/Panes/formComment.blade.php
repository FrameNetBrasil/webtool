<div class="form" style="height:260px">
    <x-form
        hx-post="/annotation/dynamicMode/updateObjectComment"
    >
        <x-slot:title>
            @if($order == 0)
                <div class="flex">
                    <div class="title">Comment for Object: #none</div>
                </div>
            @else
                <div class="flex gap-2">
                    <div class="title">Comment for Object: #{{$order}}</div>
                    <div class="flex h-2rem gap-2">
                        <div class="ui label">
                            Range
                            <div class="detail">{{$object->startFrame}}/{{$object->endFrame}}</div>
                        </div>
                        <div class="ui label wt-tag-id">
                            #{{$object->idDynamicObject}}
                        </div>
                    </div>
                    @if($object->email)
                        <div class="text-sm">Created by [{{$object->email}}] at [{{$object->createdAt}}]</div>
                    @endif
                </div>
            @endif
        </x-slot:title>
        <x-slot:fields>
            <x-hidden-field id="idDocument" value="{{$idDocument}}"></x-hidden-field>
            <x-hidden-field id="idDynamicObject" value="{{$object?->idDynamicObject}}"></x-hidden-field>
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
                onclick="annotation.objects.deleteObjectComment({{$object?->idDynamicObject}})"
            ></x-button>
        </x-slot:buttons>
    </x-form>
</div>

