@php($field = ($search->byGroup == 'domain') ? "idFramalDomain" : (($search->byGroup == 'type') ? "idFramalType" : "idFrameScenario"))
<div class="wt-datagrid flex flex-column" style="height:100%">
    <div class="datagrid-header">
        <div class="datagrid-title">
            {{$groupName}}
        </div>
    </div>
    <div class="table" style="position:relative;height:100%">
        <table id="domainTable">
            <tbody
            >
            @foreach($groups as $idGroup => $group)
                <tr
                    hx-target="#gridArea"
                    hx-swap="innerHTML"
                >
                    <td
                        hx-post="/frame/grid"
                        hx-vals='{"{{$field}}":{{$idGroup}}, "byGroup" : "{{$search->byGroup}}"}'
                        class="cursor-pointer"
                    >
                        @if($groupName == 'Scenarios')
                            <x-element.frame :name="$group['name']"></x-element.frame>
                        @else
                        {{$group['name']}}
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

