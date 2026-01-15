@if(isset($label))
<label for="{{$id}}">{{$label}}</label>
@endif
<x-search::base
    {{ $attributes }}
    name="{{$name ?? $id}}"
    placeholder="{{$placeholder ?? 'Select a class'}}"
    :search-fields="['class' => $displayValue]"
    search-url="/class/list/forSelect"
    display-name="class"
    display-field="name"
    value="{{$value ?? ''}}"
    display-value="{{ $displayValue ?? '' }}"
    value-field="idFrame"
    modal-title="{{$modalTitle ?? 'Search class'}}"
/>
