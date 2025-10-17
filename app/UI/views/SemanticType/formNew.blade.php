<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','New SemanticType']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container">
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
                                <div class="field">
                                    <label for="semanticTypeName">English name</label>
                                    <div class="ui small input">
                                        <input type="text" id="semanticTypeName" name="semanticTypeName" value="">
                                    </div>
                                </div>

                                <div class="field">
                                    <x-combobox.domain
                                        id="idDomain"
                                        label="Domain"
                                    >
                                    </x-combobox.domain>
                                </div>
                            </div>
                            <div class="extra content">
                                <button
                                    type="submit"
                                    class="ui primary button"
                                    hx-post="/semanticType/new"
                                >
                                    Add SemanticType
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</x-layout::index>
