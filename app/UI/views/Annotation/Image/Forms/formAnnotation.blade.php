@php
    if ($object->fe) {
        $object->fe->frame = $object->fe->frameName;
    }
@endphp
<form
    class="ui form"
>
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idDocument" value="{{$object->idDocument}}">
            <input type="hidden" name="idObject" value="{{$object?->idObject}}">
            <input type="hidden" name="annotationType" value="{{$annotationType}}">
            <div class="ui two column stackable grid relative">
                <div class="column pr-8">
                    <x-ui::frame-fe
                            :object="$object?->fe"
                    ></x-ui::frame-fe>
                </div>
                <div class="column pl-8">
                    <div class="field w-full">
                        <x-search::lu
                                id="idLU"
                                label="Framed Entity"
                                placeholder="Select a Framed Entity"
                                search-url="/lu/list/forSelect"
                                value="{{ old('idFrame', $object?->lu->idLU ?? '') }}"
                                display-value="{{ old('frame', $object?->lu->name ?? '') }}"
                                modal-title="Search Framed Entity"
                        ></x-search::lu>
                    </div>
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                    type="submit"
                    class="ui primary button"
                    hx-post="/annotation/image/updateObjectAnnotation"
{{--                    hx-target="#o{{$object?->idObject}}"--}}
{{--                    hx-swap="innerHTML"--}}
            >
                Save
            </button>
        </div>
    </div>
</form>
