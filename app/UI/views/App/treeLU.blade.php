@php($field = ($search->byGroup == 'domain') ? "idFramalDomain" : "idFramalType")
<div class="wt-datagrid flex flex-column" style="height:100%">
    <div class="datagrid-header">
        <div class="datagrid-title">
            LU
        </div>
    </div>
    <div class="table" style="position:relative;height:100%">
        <table id="frameFELU">
            <tbody>
            @if(($search->lu == ''))
                @foreach($fes as $idFE => $fe)
                    <tr>
                        <td>
                            <div>
                                <div style="height:1rem;line-height: 1rem;margin:2px 0 2px 0">
                                    <span class="{{$fe['iconCls']}}"></span>
                                    <span class="color_{{$fe['idColor']}}">{{$fe['name'][0]}}</span>
                                </div>
                                <div class='definition'>{{$fe['name'][1]}}</div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            @endif
            @foreach($lus as $idLU => $lu)
                <tr>
                    <td>
                        <a
                            href="/report/lu/{{$idLU}}"
                            >
                        <div>
                            <div class="flex">
                                <a href="/report/frame/{{$lu['idFrame']}}">
                                <x-element.frame :name="$lu['frameName']"></x-element.frame>
                                </a>
                                <div class="font-medium">
                                <x-element.lu :name="$lu['name'][0]"></x-element.lu>
                                </div>
                            </div>


                            <div class='definition'>{{$lu['name'][1]}}</div>
                        </div>
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>


