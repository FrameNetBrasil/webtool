<div class="form-field">
    <label for="{{$id}}">{{$label}}</label>
    <input {{$attributes}} id="{{$id}}" name="{{$id}}">
</div>
<script>
    $(function () {
        $('#{{$id}}').combobox({
            width: "250px",
            data: {{ Js::from($options) }},
            editable: false,
            prompt: '{{$placeholder}}',
            valueField: 'idSemanticType',
            textField: 'name',
        });
    });
</script>
