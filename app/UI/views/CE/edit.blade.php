<x-layout.edit>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['/cxn','Constructions'],['/cxn/' . $constructionElement->cxn->idConstruction,$constructionElement->cxn->name],['',$constructionElement->cxn->name.'.'.$constructionElement?->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <x-layout.object>
            <x-slot:name>
                <x-element.ce
                    name="{{$constructionElement->cxn->name}}.{{$constructionElement?->name}}"
                    idColor="{{$constructionElement->idColor}}"
                ></x-element.ce>
            </x-slot:name>
            <x-slot:detail>
                <div class="ui label wt-tag-id">
                    #{{$constructionElement->idConstructionElement}}
                </div>
                <div>
                    <x-combobox.ce-cxn
                        id="idConstructionlement"
                        :idConstruction="$constructionElement->cxn->idConstruction"
                        :defaultText="'Change CE'"
                    ></x-combobox.ce-cxn>
                    <script>
                        $(function() {
                            $('#idConstructionElement_dropdown').dropdown({
                                onChange: (value) => {
                                    window.location.href= `/ce/${value}/edit`;
                                }
                            });
                        });
                    </script>
                </div>
            </x-slot:detail>
            <x-slot:description>
                #{{$constructionElement->description}}
            </x-slot:description>
            <x-slot:main>
                @include("CE.menu")
            </x-slot:main>
        </x-layout.object>
    </x-slot:main>
</x-layout.edit>
