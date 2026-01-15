<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['','New SemanticType']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-content">
                    <form class="ui form">
                        <div class="ui card form-card w-full p-1">
                            <div class="content">
                                <div class="header">
                                    Create new SemanticType
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="fields">
                                    <div class="field">
                                        <label for="nameEn">Name</label>
                                        <div class="ui small input">
                                            <input type="text" id="nameEn" name="nameEn" value="">
                                        </div>
                                    </div>
                                    <div class="field">
                                        <x-search::semantictype
                                            id="idSemanticTypeParent"
                                            label="SemanticType Parent"
                                            placeholder="Select a SemanticType"
                                            search-url="/semanticType/list/forSelect"
                                            value=""
                                            display-value=""
                                            modal-title="Search SemanticType"
                                        ></x-search::semantictype>
                                    </div>

                                </div>
                            </div>
                            <div class="extra content">
                                <button
                                    type="submit"
                                    class="ui primary button"
                                    hx-post="/semanticType/new"
                                >
                                    Save
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</x-layout::index>
