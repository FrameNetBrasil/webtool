<x-layout.page>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['/utils/importFullText','Import FullText'],['',$document->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <div class="ui container h-full">
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
    </x-slot:main>
</x-layout.page>
<script>
    htmx.on("#formImportFullText", "htmx:xhr:progress", function(evt) {
        htmx.find("#progress").setAttribute("value", evt.detail.loaded / evt.detail.total * 100);
    });
</script>
