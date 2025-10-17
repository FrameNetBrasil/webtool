<x-layout.edit>
    <x-slot:head>
        <x-layout::breadcrumb :sections="[['/','Home'],['/structure','Structure'],['/frame','Frame'],['/frame/' . $frameElement->frame->idFrame,$frameElement->frame->name],['',$frameElement->frame->name.'.'.$frameElement?->name]]"></x-layout::breadcrumb>
    </x-slot:head>
    <x-slot:main>
        <x-layout.object>
            <x-slot:name>
                <x-element.fe
                    name="{{$frameElement->frame->name}}.{{$frameElement?->name}}"
                    type="{{$frameElement->coreType}}"
                    idColor="{{$frameElement->idColor}}"
                ></x-element.fe>
            </x-slot:name>
            <x-slot:detail>
                <div class="ui label wt-tag-id">
                    #{{$frameElement->idFrameElement}}
                </div>
                <div class="ui label wt-tag-en">
                    {{$frameElement->nameEn}} [en]
                </div>
                <div>
                    <x-combobox.fe-frame
                        id="idFrameElement"
                        :idFrame="$frameElement->frame->idFrame"
                        :defaultText="'Change FE'"
                    ></x-combobox.fe-frame>
                    <script>
                        $(function() {
                            $('#idFrameElement_dropdown').dropdown({
                                onChange: (value) => {
                                    window.location.href= `/fe/${value}/edit`;
                                }
                            });
                        });
                    </script>
                </div>
            </x-slot:detail>
            <x-slot:description>
                #{{$frameElement->description}}
            </x-slot:description>
            <x-slot:main>
                @include("FE.menu")
            </x-slot:main>
        </x-layout.object>
    </x-slot:main>
</x-layout.edit>
