{{--Layout of grid for children objects --}}
{{--Goal: Template for list operation - used with children.blade.php --}}
<div
    id="gridChildren"
    class="card-grid dense pt-2"
    hx-trigger="reload-gridChildren from:body"
    hx-target="this"
    hx-swap="outerHTML"
    hx-get="/url/for/object/{{$id}}/children/grid"
>
    @foreach($children as $child)
        <div
            class="ui card option-card cursor-pointer"
        >
            <div class="content overflow-hidden">
                <span class="right floated">
                    {{-- icon for delete the child--}}
                    <x-ui::delete
                        title="remove Child from Parent"
                        onclick="messenger.confirmDelete(`Removing child '{{$child->name}}' from object.`, '/url/for/object/{{$id}}/children/{{$child->id}}')"
                    ></x-ui::delete>
                </span>
                <div class="header">
                    #{{$child->id}}
                </div>
                <div class="description">
                    {{$child->name}}
                </div>
            </div>
        </div>
    @endforeach
</div>
