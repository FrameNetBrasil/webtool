<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idLayerGroup" value="{{$idLayerGroup}}">

            <div class="field">
                <label for="nameEn">Name (English)</label>
                <div class="ui small input">
                    <input type="text" id="nameEn" name="nameEn" value="">
                </div>
            </div>

            <div class="three fields">
                <div class="field">
                    <label for="layerOrder">Order</label>
                    <div class="ui small input">
                        <input type="number" id="layerOrder" name="layerOrder" value="0">
                    </div>
                </div>
                <div class="field">
                    <label>
                        <input type="checkbox" name="allowsApositional" value="1">
                        Allows Apositional
                    </label>
                </div>
                <div class="field">
                    <label>
                        <input type="checkbox" name="isAnnotation" value="1">
                        Is Annotation
                    </label>
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/layers/{{$idLayerGroup}}/layertypes/new"
            >
                Add Layer Type
            </button>
        </div>
    </div>
</form>
