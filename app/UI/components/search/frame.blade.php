@if(isset($label))
<label for="{{$id}}">{{$label}}</label>
@endif
<x-search::base
    {{ $attributes }}
    name="{{$name ?? $id}}"
    placeholder="{{$placeholder ?? 'Select a frame'}}"
    :search-fields="['frame' => $displayValue]"
    search-url="/frame/list/forSelect"
    display-name="frame"
    display-field="nsName"
    value="{{$value ?? ''}}"
    display-value="{{ $displayValue ?? '' }}"
    value-field="idFrame"
    modal-title="{{$modalTitle ?? 'Search frame'}}"
/>
