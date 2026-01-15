@if(isset($label))
<label for="{{$id}}">{{$label}}</label>
@endif
<x-search::base
    {{ $attributes }}
    name="{{$name ?? $id}}"
    placeholder="{{$placeholder ?? 'Select a microframe'}}"
    :search-fields="['microframe' => $displayValue]"
    search-url="/microframe/list/forSelect"
    display-name="microframe"
    display-field="name"
    value="{{$value ?? ''}}"
    display-value="{{ $displayValue ?? '' }}"
    value-field="idFrame"
    modal-title="{{$modalTitle ?? 'Search microframe'}}"
/>
