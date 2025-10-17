@if(isset($label))
<label for="{{$id}}">{{$label}}</label>
@endif
<x-search::base
    name="{{$id}}"
    placeholder="{{$placeholder}}"
    :search-fields="['lu' => $displayValue]"
    search-url="/lu/list/forSelect"
    display-formatter="displayFormaterLUSearch"
    display-name="lu"
    display-field="name"
    value="{{$value}}"
    display-value="{{ $displayValue }}"
    value-field="idLU"
    modal-title="{{$modalTitle}}"
/>
<script>
    function displayFormaterLUSearch(lu) {
        console.log(lu);
        return `<div class="result"><span class="color_frame">${lu.frameName}</span>.${lu.name}</span></div>`;
    };
</script>
