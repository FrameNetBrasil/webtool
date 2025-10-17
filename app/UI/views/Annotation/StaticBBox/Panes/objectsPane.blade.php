<div class="ui tab active objects" data-tab="objects">
    <table id="gridObjects" class="ui compact striped table">
        <thead>
        <tr>
            <th></th>
            <th></th>
            <th class="text-right">#object</th>
            <th class="text-right">#id</th>
            <th>FrameElement</th>
            <th>Framed entity</th>
        </tr>
        </thead>
        <tbody>
        <template x-data x-for="object,index in $store.doStore.objects">
            <tr
                :id="'so_' + object.idStaticObject"
                @click="Alpine.store('doStore').selectObject(index + 1)"
                class="cursor-pointer w-full text-color"
                :class="{
    'ui yellow': object.comment,
    'ui green' : object.fe && !object.comment,
    'ui red': !object.fe && !object.comment,
    'ui blue' : Alpine.store('doStore').currentObject && ($store.doStore.objects[index].idStaticObject === Alpine.store('doStore').currentObject.object.idStaticObject)
}"
            >
                <td class="w-2rem">
                    <x-delete
                        title="delete Object"
                        @click.stop="annotation.objects.deleteObject(object.idStaticObject)"
                    ></x-delete>
                </td>
                <td class="w-2rem">
                    <x-comment
                        @click.stop="Alpine.store('doStore').commentObject(object.idStaticObject)"
                    ></x-comment>
                </td>
                <td class="text-right w-5rem">
                    <div class="objectId" x-text="'#' + (index + 1)"></div>
                </td>
                <td class="text-right w-6rem">
                    #<span x-text="object.idStaticObject"></span>
                </td>
                <td>
                    <template x-if="object.fe">
                        <div><i class="icon material color_frame">dashboard</i><span
                                x-text="object.frame + '.' + object.fe"></span></div>
                    </template>
                </td>
                <td>
                    <template x-if="object.lu">
                        <div><i class="icon material color_lu">abc</i>
                            <span x-text="object.lu"></span>
                        </div>
                    </template>
                </td>
            </tr>
        </template>
        </tbody>
    </table>
</div>


{{--<div class="ui tab active objects p-2" data-tab="objects">--}}
{{--    <div--}}
{{--        id="gridObjects"--}}
{{--        class="grid"--}}
{{--    >--}}
{{--        <template x-data x-for="object,index in $store.doStore.objects">--}}
{{--            <div class="col-4">--}}
{{--                <div--}}
{{--                    @click="Alpine.store('doStore').selectObject(index + 1)"--}}
{{--                    :class="'ui card cursor-pointer w-full ' + (object.fe ? 'filled' : 'empty')"--}}
{{--                >--}}
{{--                    <div class="content">--}}
{{--                            <span class="right floated">--}}
{{--                                <x-delete--}}
{{--                                    title="delete Object"--}}
{{--                                    @click.stop="annotation.objects.deleteObject(object.idStaticObject)"--}}
{{--                                ></x-delete>--}}
{{--                            </span>--}}
{{--                        <div--}}
{{--                            class="header"--}}
{{--                        >--}}
{{--                            <div--}}
{{--                            >--}}
{{--                                <div class="flex">--}}
{{--                                    <div class="objectId" x-text="'#' + (index + 1)"></div>--}}
{{--                                </div>--}}
{{--                            </div>--}}
{{--                        </div>--}}
{{--                        <div class="description">--}}
{{--                            <div class="ui label mb-1">--}}
{{--                                #<span x-text="object.idStaticObject"></span>--}}
{{--                            </div>--}}
{{--                            <template x-if="object.fe">--}}
{{--                                <div><i class="icon material color_frame">dashboard</i><span--}}
{{--                                        x-text="object.frame + '.' + object.fe"></span></div>--}}
{{--                            </template>--}}
{{--                            <template x-if="object.lu">--}}
{{--                                <div><i class="icon material color_lu">abc</i><span x-text="object.luFrameName + '.' + object.luName"></span></div>--}}
{{--                            </template>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </template>--}}
{{--    </div>--}}
{{--</div>--}}
