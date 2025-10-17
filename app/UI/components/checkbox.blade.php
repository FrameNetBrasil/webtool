<label for="{{$id}}"></label>
<div {{$attributes}} class="ui checkbox {!! $active ? 'checked' : '' !!}">
    <input type="checkbox" name="{{$id}}" id="{{$id}}" value="1" {!! $active ? 'checked' : '' !!}>
    <label>{{$label}}</label>
</div>
