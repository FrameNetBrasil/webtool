<div class="ui card form-card w-full p-1">
    <div class="content">
        <div class="header">
            {{--            <x-icon::add></x-icon::add>--}}
            Create new Object
        </div>
        <div class="description">

        </div>
    </div>
    <div class="content">
        {{--        @if($annotationType == "dynamicMode")--}}
        {{--            <button--}}
        {{--                class="ui button primary"--}}
        {{--                @click="$dispatch('bbox-create')"--}}
        {{--            >--}}
        {{--                <i class="plus square outline icon"></i>--}}
        {{--                Create BBox--}}
        {{--            </button>--}}
        {{--        @endif--}}
        {{--        @if($annotationType == "deixis")--}}
        <form class="ui form">
            <input type="hidden" name="idDocument" value="{{$idDocument}}">
            <input type="hidden" name="idDynamicObject" value="0">
            <input type="hidden" name="annotationType" value="{{$annotationType}}">
            @if($annotationType == "dynamicMode")
                <input type="hidden" name="idLayerType" value="0">
                <div class="fields">
                    <div class="field">
                        <label>Start frame <span class="text-primary cursor-pointer"
                                                 @click="copyFrameFor('startFrame')">[Copy from video]</span></label>
                        <div class="ui medium input">
                            <input type="text" name="startFrame" placeholder="1" value="1">
                        </div>
                    </div>
                </div>
            @endif
            @if($annotationType == "deixis")
                <div class="three fields">
                    <div class="field">
                        <x-combobox::layer-deixis
                            label="Layer type"
                            id="idLayerType"
                            :value="0"
                        ></x-combobox::layer-deixis>
                    </div>
                    <div class="field">
                        <label>Start frame <span class="text-primary cursor-pointer"
                                                 @click="copyFrameFor('startFrame')">[Copy from video]</span></label>
                        <div class="ui medium input">
                            <input type="text" name="startFrame" placeholder="1" value="1">
                        </div>
                    </div>
                    <div class="field">
                        <label>End frame <span class="text-primary cursor-pointer" @click="copyFrameFor('endFrame')">[Copy from video]</span></label>
                        <div class="ui medium input">
                            <input type="text" name="endFrame" placeholder="1" value="1">
                        </div>
                    </div>
                </div>
            @endif
            @if($annotationType == "canvas")
                <div class="three fields">
                    <div class="field">
                        <x-combobox::layer-canvas
                            label="Layer type"
                            id="idLayerType"
                            :value="0"
                        ></x-combobox::layer-canvas>
                    </div>
                    <div class="field">
                        <label>Start frame <span class="text-primary cursor-pointer"
                                                 @click="copyFrameFor('startFrame')">[Copy from video]</span></label>
                        <div class="ui medium input">
                            <input type="text" name="startFrame" placeholder="1" value="1">
                        </div>
                    </div>
                    <div class="field">
                        <label>End frame <span class="text-primary cursor-pointer" @click="copyFrameFor('endFrame')">[Copy from video]</span></label>
                        <div class="ui medium input">
                            <input type="text" name="endFrame" placeholder="1" value="1">
                        </div>
                    </div>
                </div>
            @endif
            <button
                type="submit"
                class="ui primary button"
                hx-post="/annotation/video/createNewObjectAtLayer"
            >
                Create
            </button>
        </form>
    </div>
</div>

