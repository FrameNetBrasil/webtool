<div class="grid grid-nogutter h-full">
    <div id="cxnTableContainer" class="col">
        <div class="wt-datagrid flex flex-column" style="height:100%">
            <div class="table" style="position:relative;height:100%">
                <table id="cxnTable">
                    <tbody>
                    @foreach($cxns as $idConstruction => $cxn)
                        <tr
                        >
                            <td
                                class="cursor-pointer name"
                            >
                                <div>
                                    <a href="/cxn/{{$idConstruction}}">
                                        <x-element.construction :name="$cxn['name'][0]"></x-element.construction>
                                        <div class='definition'>{{$cxn['name'][1]}}</div>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
