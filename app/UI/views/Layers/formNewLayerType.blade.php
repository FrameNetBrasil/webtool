<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','New LayerType']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container">
                <div class="page-content">
                    <form class="ui form">
                        <div class="ui card form-card w-full p-1">
                            <div class="content">
                                <div class="header">
                                    Create new LayerType
                                </div>
                                <div class="description">

                                </div>
                            </div>
                            <div class="content">
                                <div class="field">
                                    <label for="nameEn">English Name</label>
                                    <div class="ui small input">
                                        <input type="text" id="nameEn" name="nameEn" value="">
                                    </div>
                                </div>

                                <div class="fields">
                                    <div class="field">
                                        <x-combobox.layer-group
                                            id="idLayerGroup"
                                            label="LayerGroup"
                                            :value="0"
                                        >
                                        </x-combobox.layer-group>
                                    </div>
                                    <div class="field">
                                        <x-number-field
                                            id="layerOrder"
                                            label="LayerOrder"
                                            :value="0"
                                        >
                                        </x-number-field>
                                    </div>
                                </div>

                                <div class="fields">
                                    <div class="field">
                                        <x-checkbox
                                            id="allowsApositional"
                                            label="Allows Apositional"
                                            :active="false"
                                        >
                                        </x-checkbox>
                                    </div>
                                    <div class="field">
                                        <x-checkbox
                                            id="isAnnotation"
                                            label="Is Annotation"
                                            :active="false"
                                        >
                                        </x-checkbox>
                                    </div>
                                </div>
                            </div>
                            <div class="extra content">
                                <button
                                    type="submit"
                                    class="ui primary button"
                                    hx-post="/layers/layertype/new"
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
