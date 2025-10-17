<div class="wt-datagrid flex flex-column" style="height:100%">
    <div class="datagrid-header">
        <div class="datagrid-title">
            Frame
        </div>
    </div>
    <div class="table" style="position:relative;height:100%">
        <table id="frameTable">
            <tbody>
            @foreach($frames as $idFrame => $frame)
                <tr
                >
                    <td
                        class="cursor-pointer name"
                    >
                        <div>
                            <a href="/report/frame/{{$idFrame}}">
                                <x-element.frame :name="$frame['name'][0]"></x-element.frame>
                                <div class='definition'>{{$frame['name'][1]}}</div>
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>


