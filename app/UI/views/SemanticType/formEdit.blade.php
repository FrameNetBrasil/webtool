<form class="ui form">
    <div class="ui card form-card w-full p-1">
        <div class="content">
            <input type="hidden" name="idSemanticType" value="{{$semanticType->idSemanticType}}">

            <div class="field">
                <x-search::semantictype
                    id="idSemanticTypeParent"
                    label="SemanticType Parent"
                    placeholder="Select a SemanticType"
                    search-url="/semanticType/list/forSelect"
                    value="{{ old('idSemanticTypeParent', $semanticType->parent->idSemanticType ?? '') }}"
                    display-value="{{ old('frame', $semanticType->parent->name ?? '') }}"
                    modal-title="Search SemanticType"
                ></x-search::semantictype>
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
