<x-datagrid
    id="gridFERelation"
    title=""
    type="child"
    extraTitle="<span class='color_frame'>{{$frame->name}}&nbsp;</span><span class='color_{{$relation->entry}}'>{{$relation->name}}</span><span class='color_frame'>&nbsp;{{$relatedFrame->name}}</span>"
>
    <x-slot:header>
        <thead>
        <td class="wt-datagrid-action">
        </td>
        <td>
            <span>{{$frame->name}}</span>
        </td>
        <td>
            <span>{{$relatedFrame->name}}</span>
        </td>
        </thead>
    </x-slot:header>
    @foreach($relations as $relation)
        <tr>
            <td>
                <span class="{{$relation['feIconCls']}}"></span>
                <span class="color_{{$relation['feIdColor']}}">{{$relation['feName']}}</span>
            </td>
            <td>
                <span class="{{$relation['relatedFEIconCls']}}"></span>
                <span class="color_{{$relation['relatedFEIdColor']}}">{{$relation['relatedFEName']}}</span>
            </td>
        </tr>
    @endforeach
</x-datagrid>
