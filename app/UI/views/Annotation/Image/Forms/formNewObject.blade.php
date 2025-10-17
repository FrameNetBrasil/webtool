<div class="ui card form-card w-full h-full">
    <div class="content flex-none">
        <div class="header">
            {{--            <x-icon::add></x-icon::add>--}}
            Create new Object
        </div>
        <div class="description">

        </div>
    </div>
    <div class="content">
        <form class="ui form">
            <input type="hidden" name="idDocument" value="{{$idDocument}}">
            <input type="hidden" name="idStaticObject" value="0">
            <input type="hidden" name="annotationType" value="{{$annotationType}}">
            @if($annotationType == "staticBBox")
                <input type="hidden" name="idLayerType" value="0">
{{--                <div class="fields">--}}
{{--                    <div class="field">--}}
{{--                        <label>Start frame <span class="text-primary cursor-pointer"--}}
{{--                                                 @click="copyFrameFor('startFrame')">[Copy from video]</span></label>--}}
{{--                        <div class="ui medium input">--}}
{{--                            <input type="text" name="startFrame" placeholder="1" value="1">--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                </div>--}}
            @endif
            <button
                type="submit"
                class="ui primary button"
                @click.prevent="$dispatch('bbox-create')"
            >
                Create
            </button>
        </form>
    </div>
</div>

