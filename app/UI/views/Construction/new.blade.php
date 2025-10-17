<x-layout.edit>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['','New Construction']]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <x-form
            title="New Construction"
            hx-post="/cxn"
        >
            <x-slot:fields>
                <div class="field">
                     <x-text-field
                         id="nameEn"
                         label="English Name"
                         value="">

                     </x-text-field>
                </div>
                <div class="field">
                    <x-combobox.cxn-language
                        id="idLanguage"
                        value=""
                    ></x-combobox.cxn-language>
                </div>
            </x-slot:fields>
            <x-slot:buttons>
                <x-submit label="Add Construction"></x-submit>
            </x-slot:buttons>
        </x-form>
    </x-slot:main>
</x-layout.edit>
