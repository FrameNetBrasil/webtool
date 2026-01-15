<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idLayerType" value="{{$idLayerType}}">
            <input type="hidden" name="idLanguage" value="2">
            <input type="hidden" name="idUser" value="{{auth()->user()->idUser ?? 1}}">

            <div class="field">
                <label for="name">Name</label>
                <div class="ui small input">
                    <input type="text" id="name" name="name" value="">
                </div>
            </div>

            <div class="field">
                <label for="definition">Definition</label>
                <div class="ui small input">
                    <textarea id="definition" name="definition" rows="3"></textarea>
                </div>
            </div>

            <div class="field">
                <label for="idColor">Color ID</label>
                <div class="ui small input">
                    <input type="number" id="idColor" name="idColor" value="1">
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/layertype/{{$idLayerType}}/genericlabels/new"
            >
                Add Generic Label
            </button>
        </div>
    </div>
</form>
