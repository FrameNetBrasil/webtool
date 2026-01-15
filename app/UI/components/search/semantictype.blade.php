@if(isset($label))
<label for="{{$id}}">{{$label}}</label>
@endif
<x-search::base
    name="{{$id}}"
    placeholder="{{$placeholder}}"
    :search-fields="['semanticType' => $displayValue]"
    search-url="/semanticType/list/forSelect"
    display-formatter=""
    display-name="semanticType"
    display-field="name"
    value="{{$value}}"
    display-value="{{ $displayValue }}"
    value-field="idSemanticType"
    modal-title="{{$modalTitle}}"
/>
