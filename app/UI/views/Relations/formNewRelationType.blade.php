<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','New RelationType']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container">
                <div class="page-content">
                    <form class="ui form">
                        <div class="ui card form-card w-full p-1">
                            <div class="content">
                                <div class="header">
                                    Create new RelationType
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="fields">
                                    <div class="field">
                                        <x-combobox.relation-group
                                            id="idRelationGroup"
                                            label="RelationGroup"
                                            :value="0"
                                        >
                                        </x-combobox.relation-group>
                                    </div>
                                </div>

                                <div class="field">
                                    <label for="nameCanonical">Canonical Name</label>
                                    <div class="ui small input">
                                        <input type="text" id="nameCanonical" name="nameCanonical" value="">
                                    </div>
                                </div>
                            </div>
                            <div class="extra content">
                                <button
                                    type="submit"
                                    class="ui primary button"
                                    hx-post="/relations/relationtype/new"
                                >
                                    Add RelationType
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</x-layout::index>
