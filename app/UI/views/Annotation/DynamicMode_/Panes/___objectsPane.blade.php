@php
$id = "gridObjects";
@endphp
<div class="ui tab active objects h-full" data-tab="objects">
    <div id="gridObjectsWrapper" class="ui compact striped table h-full">
        <table id="{{$id}}" >

        </table>
    </div>
</div>
<script>
    $(function() {
        $("#{{$id}}").datagrid({
            data: [],
            fit: true,
            showHeader: false,
            rownumbers: false,
            showFooter: false,
            border: false,
            singleSelect:true,
            idField:"idDynamicObject",
            columns: [[
                {
                    field:"delete",
                    formatter: function(value, row, index) {
                        return `<i class="red times icon cursor-pointer"></i>`
                    }
                },
                {
                    field: "idDynamicObject",
                    width: "100%",
                    formatter: function(value, row, index) {
                        return value;
                    }
                }
            ]],
            onSelect: (index, row) => {
                console.log(row);
                Alpine.store('doStore').selectObjectByIdDynamicObject(row.idDynamicObject);
            },
            onClickCell: (index, field, value) => {
                let rows =  $("#{{$id}}").datagrid("getRows");
                let row = rows[index];
                if (field == "delete") {
                    annotation.objects.deleteObject(row.idDynamicObject);
                }
            },
            onClickRow: (index, row) => {
            }
        });
    });
</script>


{{--<div class="ui tab active objects" data-tab="objects">--}}
{{--    <table id="gridObjects" class="ui compact striped table">--}}
{{--        <thead>--}}
{{--        <tr>--}}
{{--            <th></th>--}}
{{--            <th></th>--}}
{{--            <th class="text-right">#object</th>--}}
{{--            <th  class="text-right">Start</th>--}}
{{--            <th  class="text-right">End</th>--}}
{{--            <th  class="text-right">#id</th>--}}
{{--            <th>FrameElement</th>--}}
{{--            <th>CVName</th>--}}
{{--        </tr>--}}
{{--        </thead>--}}
{{--        <tbody>--}}
{{--        <template--}}
{{--            x-for="object,index in $store.doStore.objects"--}}
{{--        >--}}
{{--            <tr--}}
{{--                :id="'do_' + object.idDynamicObject"--}}
{{--                @click="Alpine.store('doStore').selectObject(index + 1)"--}}
{{--                :class="'cursor-pointer w-full text-color ' + ((object.idObject === Alpine.store('doStore').currentObject?.idObject) ? 'ui blue' : (object.comment ? 'ui yellow' : (object.fe ? 'ui green' : 'ui red')))"--}}
{{--            >--}}
{{--                <td class="w-2rem">--}}
{{--                    <x-delete--}}
{{--                        title="delete Object"--}}
{{--                        @click.stop="annotation.objects.deleteObject(object.idDynamicObject)"--}}
{{--                    ></x-delete>--}}
{{--                </td >--}}
{{--                <td class="w-2rem">--}}
{{--                    <x-comment--}}
{{--                        @click.stop="Alpine.store('doStore').commentObject(object.idDynamicObject)"--}}
{{--                    ></x-comment>--}}
{{--                </td >--}}
{{--                <td class="text-right w-6rem">--}}
{{--                    <div class="objectId" x-text="'#' + (index + 1)"></div>--}}
{{--                </td>--}}
{{--                <td class="text-right w-6rem">--}}
{{--                    <div class="detail"><span x-text="object.startFrame"></span></div>--}}
{{--                </td>--}}
{{--                <td  class="text-right w-6rem">--}}
{{--                    <div class="detail"--}}
{{--                         @click.stop="Alpine.store('doStore').selectObject(index + 1);annotation.video.gotoFrame(object.endFrame)">--}}
{{--                        <span x-text="object.endFrame"></span></div>--}}
{{--                </td>--}}
{{--                <td class="text-right w-6rem">--}}
{{--                    #<span x-text="object.idDynamicObject"></span>--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <template x-if="object.fe">--}}
{{--                        <div><i class="icon material color_frame">dashboard</i><span--}}
{{--                                x-text="object.frame + '.' + object.fe"></span></div>--}}
{{--                    </template>--}}
{{--                </td>--}}
{{--                <td>--}}
{{--                    <template x-if="object.lu">--}}
{{--                        <div><i class="icon material color_lu">abc</i>--}}
{{--                            <span x-text="object.lu"></span>--}}
{{--                        </div>--}}
{{--                    </template>--}}
{{--                </td>--}}
{{--            </tr>--}}
{{--        </template>--}}
{{--        </tbody>--}}
{{--    </table>--}}
{{--</div>--}}


