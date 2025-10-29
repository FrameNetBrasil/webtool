<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idSemanticType" value="{{$semanticType->idSemanticType}}">

            <div class="field">
                <label for="name">Name</label>
                <div class="ui small input">
                    <input type="text" id="name" name="name" value="{{$semanticType->name}}">
                </div>
            </div>

            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description">{{$semanticType->description}}</textarea>
            </div>

            <div class="field">
                <x-combobox.domain
                    label="Domain"
                    id="idDomain"
                    :value="$semanticType->idDomain"
                ></x-combobox.domain>
            </div>
        </div>
        <div class="extra content">
            <button
                type="submit"
                class="ui primary button"
                hx-post="/semanticType"
            >
                Save
            </button>
        </div>
    </div>
</form>
