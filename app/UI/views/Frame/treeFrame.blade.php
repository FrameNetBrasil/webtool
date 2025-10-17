@php($hxVals = '')
@if($search->byGroup == 'domain')
    @php($hxVals = ",\"idFramalDomain\":{$search->idFramalDomain}")
@endif
@if($search->byGroup == 'type')
    @php($hxVals = ",\"idFramalType\":{$search->idFramalType}")
@endif

<div class="wt-datagrid flex flex-column" style="height:100%">
{{--    <div class="datagrid-header">--}}
{{--            {{$currentGroup ?: $search->frame ?: "Frames"}}--}}
{{--    </div>--}}
    <div class="table" style="position:relative;height:100%;">
        <table id="frameTable">
            <tbody>
            @foreach($frames as $idFrame => $frame)
                <tr
                >
                    <td
{{--                        hx-post="/frame/grid"--}}
{{--                        hx-vals='{"idFrame":{{$idFrame}},"group":"{{$search->byGroup}}"}'--}}
{{--                        hx-target="#feluTableContainer"--}}
{{--                        hx-swap="outerHTML"--}}
                        class="cursor-pointer name"
                        style="width:90%"
                    >
                        <div>
                            <a href="/frame/{{$idFrame}}">
                                <x-element.frame :name="$frame['name'][0]"></x-element.frame>
{{--                            <div class="flex justify-content-between">--}}
{{--                                <div class='color_frame' style="height:1rem;line-height: 1rem;margin:2px 0 2px 0">--}}
{{--                                    <span class="{{$frame['iconCls']}}"></span>--}}

{{--                                    <span><x-icon.frame></x-icon.frame>{{$frame['name'][0]}}</span>--}}
{{--                                </div>--}}
{{--                            </div>--}}
                                <div class='definition'>{{$frame['name'][1]}}</div>
                            </a>
                        </div>
                    </td>
                    <td
                    >
                        {{$frame["domain"]}}
                    </td>
{{--                    <td--}}
{{--                        class="edit"--}}
{{--                    >--}}
{{--                        <div>--}}
{{--                            <x-button--}}
{{--                                class="hxBtn hxSm"--}}
{{--                                href="frame/{{$idFrame}}"--}}
{{--                                label="edit"--}}
{{--                                color="secondary"--}}
{{--                            >--}}
{{--                            </x-button>--}}
{{--                        </div>--}}
{{--                    </td>--}}
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>


