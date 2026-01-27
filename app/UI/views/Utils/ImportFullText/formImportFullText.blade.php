<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/utils/importFullText','Import Fulltext'],['', $document->name]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">Import fulltext</span>
                        </div>
                    </div>
                </div>

                <div class="page-content">
                    <x-form
                        id="formImportFullText"
                        title="{{$document->name}}"
                        hx-encoding='multipart/form-data'
                        hx-post="/utils/importFullText"
                        class="p-2"
                    >
                        <x-slot:fields>
                            <x-hidden-field
                                id="idDocument"
                                :value="$document->idDocument"
                            ></x-hidden-field>
                            <div class="field">
                                <x-combobox.language
                                    label="Language"
                                    id="idLanguage"
                                    value=""
                                ></x-combobox.language>
                            </div>
                            <div class="field">
                                <x-file-field
                                    label="File"
                                    id="file"
                                    value=""
                                ></x-file-field>
                            </div>
                            <div class="field">
                                <progress id='progress' value='0' max='100'></progress>
                            </div>
                        </x-slot:fields>
                        <x-slot:buttons>
                            <x-submit label="Save"></x-submit>
                        </x-slot:buttons>
                    </x-form>
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
