<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idLayerType" value="{{$layerType->idLayerType}}">

            <div class="field">
                <label for="entry">Entry</label>
                <div class="ui small input">
                    <input type="text" id="entry" name="entry" value="{{$layerType->entry}}">
                </div>
            </div>

            <div class="three fields">
                <div class="field">
                    <label for="layerOrder">Order</label>
                    <div class="ui small input">
                        <input type="number" id="layerOrder" name="layerOrder" value="{{$layerType->layerOrder}}">
                    </div>
                </div>
                <div class="field">
                    <label>
                        <input type="checkbox" name="allowsApositional" value="1" {{$layerType->allowsApositional ? 'checked' : ''}}>
                        Allows Apositional
                    </label>
                </div>
                <div class="field">
                    <label>
                        <input type="checkbox" name="isAnnotation" value="1" {{$layerType->isAnnotation ? 'checked' : ''}}>
                        Is Annotation
                    </label>
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-put="/layertype"
            >
                Save
            </button>
        </div>
    </div>
</form>
