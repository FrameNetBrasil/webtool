<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idGenericLabel" value="{{$genericLabel->idGenericLabel}}">

            <div class="field">
                <label for="name">Name</label>
                <div class="ui small input">
                    <input type="text" id="name" name="name" value="{{$genericLabel->name}}">
                </div>
            </div>

            <div class="field">
                <label for="definition">Definition</label>
                <div class="ui small input">
                    <textarea id="definition" name="definition" rows="3">{{$genericLabel->definition}}</textarea>
                </div>
            </div>

            <div class="field">
                <label for="idColor">Color ID</label>
                <div class="ui small input">
                    <input type="number" id="idColor" name="idColor" value="{{$genericLabel->idColor}}">
                </div>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-put="/genericlabel"
            >
                Save
            </button>
        </div>
    </div>
</form>
