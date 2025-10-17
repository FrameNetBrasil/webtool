<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idDocument" value="{{$object->idDocument}}">
            <input type="hidden" name="idObject" value="{{$object->idObject}}">
            <input type="hidden" name="annotationType" value="{{$annotationType}}">
            <div class="two fields">
                <div class="field">
                    <label>New start frame <span class="text-primary cursor-pointer"
                                                 @click="copyFrameFor('startFrame')">[Copy from video]</span></label>
                    <div class="ui medium input">
                        <input type="text" name="startFrame" placeholder="0" value="{{$object->startFrame}}">
                    </div>
                </div>
                <div class="field">
                    <label>New end frame <span class="text-primary cursor-pointer" @click="copyFrameFor('endFrame')">[Copy from video]</span></label>
                    <div class="ui medium input">
                        <input type="text" name="endFrame" placeholder="0" value="{{$object->endFrame}}">
                    </div>
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/annotation/video/updateObjectRange"
            >
                Save
            </button>
        </div>
    </div>
</form>
