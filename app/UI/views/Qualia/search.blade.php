<x-form-search id="framesFormSearch">
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    <x-combobox.qualia-type id="idQualiaType" value="" label="Type"></x-combobox.qualia-type>
    <x-input-field id="info" value="" placeholder="Search Relation"></x-input-field>
    <x-input-field id="frame" value="" placeholder="Search Frame"></x-input-field>
    <x-input-field id="fe"  value="" placeholder="Search FE"></x-input-field>
    <x-submit label="Search"  hx-post="/frames/grid" hx-target="#qualiaGrid"></x-submit>
    <x-button label="New Qualia Structure" color="secondary" href="/qualia/new"></x-button>
</x-form-search>
