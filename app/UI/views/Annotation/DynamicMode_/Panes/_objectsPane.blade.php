<div class="ui tab active objects p-2" data-tab="objects">
    <div
        id="gridObjects"
        class="grid"
    >
        <template x-for="object,index in objects">
            <div class="col-4">
                <div
                    @click="Alpine.store('doStore').selectObject(index + 1)"
                    :class="'ui card cursor-pointer w-full ' + (object.fe ? 'filled' : 'empty')"
                >
                    <div class="content">
                            <span class="right floated">
                                <x-delete
                                    title="delete Object"
                                    @click.stop="annotation.objects.deleteObject(object.idDynamicObject)"
                                ></x-delete>
                            </span>
                        <div
                            class="header"
                        >
                            <div
                            >
                                <div class="flex">
                                    <div class="objectId" x-text="'#' + (index + 1)"></div>
                                </div>
                            </div>
                        </div>
                        <div class="description">
                            <div class="ui label mb-1">
                                Start
                                <div class="detail"><span x-text="object.startFrame"></span></div>
                            </div>
                            <div class="ui label mb-1">
                                End
                                <div class="detail" @click.stop="Alpine.store('doStore').selectObject(index + 1);annotation.video.gotoFrame(object.endFrame)"><span x-text="object.endFrame"></span></div>
                            </div>
                            <div class="ui label mb-1">
                                #<span x-text="object.idDynamicObject"></span>
                            </div>
                            <template x-if="object.fe">
                                <div><i class="icon material color_frame">dashboard</i><span
                                        x-text="object.frame + '.' + object.fe"></span></div>
                            </template>
                            <template x-if="object.lu">
                                <div><i class="icon material color_lu">abc</i><span x-text="object.luFrameName + '.' + object.luName"></span></div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
