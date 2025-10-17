<div class="form-field">
    <label for="{{$id}}">{{$label}}</label>
    <input {{$attributes}} id="{{$id}}" name="{{$id}}">
</div>
<script>
    $(function () {
        $('#{{$id}}').combobox({
            valueField: 'idQualiaType',
            textField: 'name',
            mode: 'remote',
            method: 'get',
            url: "/qualia/listTypesForSelect"
        });
    });
</script>
