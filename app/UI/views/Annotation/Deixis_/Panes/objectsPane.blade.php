<div class="ui tab objects" data-tab="objects">
    <table id="gridObjects" class="ui compact striped table">
        <thead>
        <tr>
            <th></th>
            <th></th>
            <th class="text-right">#object</th>
            <th class="text-right">Start</th>
            <th class="text-right">End</th>
            <th class="text-right">#id</th>
            <th>FrameElement</th>
            <th>CVName/Label</th>
            <th>BBox?</th>
        </tr>
        </thead>
        <template
            x-for="layer,indexLayer in $store.doStore.layers"
            x-init="Alpine.store('doStore').initObjectIndex()"
        >
            <tbody>
                <tr
                >
                    <td colspan="9">
                        <div class="layerRow"><span x-text="layer.layer"></span></div>
                    </td>
                </tr>
                <template
                    x-for="object,index in layer.objects"
                 >
                    <tr
                        :id="'do_' + object.idDynamicObject"
                        @click="Alpine.store('doStore').selectObjectByIdDynamicObject(object.idDynamicObject)"
                        class="cursor-pointer w-full text-color"
                        :class="{
                                    'ui yellow': object.comment,
                                    'ui green' : (object.lu || object.gl) && !object.comment,
                                    'ui red': !(object.lu || object.gl) && !object.comment,
                                    'ui blue' : Alpine.store('doStore').currentObject && ($store.doStore.layers[indexLayer].objects[index].idDynamicObject === Alpine.store('doStore').currentObject.idDynamicObject)
                                }"
                        x-init="oIndex = ++Alpine.store('doStore').objectIndex"
                    >
                        <td class="w-2rem">
                            <x-delete
                                title="delete Object"
                                @click.stop="annotation.objects.deleteObject(object.idDynamicObject)"
                            ></x-delete>
                        </td>
                        <td class="w-2rem">
                            <x-comment
                                @click.stop="Alpine.store('doStore').commentObject(object.idDynamicObject)"
                            ></x-comment>
                        </td>
                        <td class="text-right w-5rem">
                            <div class="objectId" x-text="'#' + (oIndex)"></div>
                        </td>
                        <td class="text-right w-6rem">
                            <div class="detail"><span x-text="object.startFrame"></span></div>
                        </td>
                        <td class="text-right w-6rem">
                            <div class="detail"
                                 @click.stop="Alpine.store('doStore').selectObjectByIdDynamicObject(object.idDynamicObject, object.endFrame)">
                                <span x-text="object.endFrame"></span></div>
                        </td>
                        <td class="text-right w-6rem">
                            #<span x-text="object.idDynamicObject"></span>
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
                            <template x-if="object.gl">
                                <div><i class="icon material color_lu">abc</i>
                                    <span x-text="object.gl"></span>
                                </div>
                            </template>
                        </td>
                        <td>
                            <span x-show="object.bboxes.length > 0"><x-icon.checked ></x-icon.checked></span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </template>
    </table>
</div>


