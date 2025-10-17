<div {{$attributes->class(["wt-datagrid","wt-container-center-content" => $center])}} >
    @if($title != '')
    <div class="datagrid-header">
        <div class="datagrid-title">{{$title}}{!! $extraTitle !!}</div>
    </div>
    @endif
    <div class="table">
        <table>
            {{$header}}
            {{$thead}}
            <tbody id="{{$id}}" style="height:{{$height}}">
            {{$slot}}
            </tbody>
        </table>
    </div>
</div>
