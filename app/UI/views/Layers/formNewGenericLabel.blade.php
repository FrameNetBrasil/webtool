<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','New GenericLabel']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container">
                <div class="page-content">
                    <form class="ui form">
                        <div class="ui card form-card w-full p-1">
                            <div class="content">
                                <div class="header">
                                    Create new GenericLabel
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="field">
                                    <label for="name">Name</label>
                                    <div class="ui small input">
                                        <input type="text" id="name" name="name" value="">
                                    </div>
                                </div>

                                <div class="field">
                                    <label for="definition">Definition</label>
                                    <textarea id="definition" name="definition"></textarea>
                                </div>

                                <div class="three fields">
                                    <div class="field">
                                        <x-combobox.language
                                            id="idLanguage"
                                            label="Language"
                                            value=""
                                        >
                                        </x-combobox.language>
                                    </div>
                                    <div class="field">
                                        <x-combobox.color
                                            id="idColor"
                                            label="Color"
                                            value=""
                                            placeholder="Color"
                                        >
                                        </x-combobox.color>
                                    </div>
                                    <div class="field">
                                        <x-combobox.layer-type
                                            id="idLayerType"
                                            label="LayerType"
                                            :value="0"
                                        >
                                        </x-combobox.layer-type>
                                    </div>
                                </div>
                            </div>
                            <div class="extra content">
                                <button
                                    type="submit"
                                    class="ui primary button"
                                    hx-post="/layers/genericlabel/new"
                                    hx-target="#editarea"
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
