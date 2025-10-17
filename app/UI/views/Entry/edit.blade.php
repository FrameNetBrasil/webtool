<x-form
    id="entryForm"
    title="Translations"
    center="true"
    :border="true"
>
    <x-slot:fields>
        <div id="frmEntries" class="ui secondary menu">
            @foreach($languages as $language)
                @php
                    $idLanguage = $language->idLanguage;
                    $description = mb_ereg_replace("\r\n","\\n",$entries[$idLanguage]->description);
                @endphp
                <a class="item" data-tab="tab{{$idLanguage}}">{{$language->description}}</a>
            @endforeach
        </div>
        @foreach($languages as $language)
            @php
                $idLanguage = $language->idLanguage;
                $description = mb_ereg_replace("\r\n","\\n",$entries[$idLanguage]->description);
            @endphp
            <div class="ui tab segment" data-tab="tab{{$idLanguage}}">
                <x-hidden-field
                    id="idEntry[{{$idLanguage}}]"
                    :value="$entries[$idLanguage]->idEntry"
                ></x-hidden-field>
                <div class="field">
                    <x-text-field
                        label="Name"
                        id="name[{{$idLanguage}}]"
                        :value="$entries[$idLanguage]->name"
                    ></x-text-field>
                </div>
                <div class="field">
                    <x-multiline-field
                        label="Definition"
                        id="description[{{$idLanguage}}]"
                        value="{!! $description !!}"
                        rows="12"
                    >
                    </x-multiline-field>
                </div>
            </div>
        @endforeach


        {{--            <hx-tablist>--}}
        {{--                @foreach($languages as $language)--}}
        {{--                    @php--}}
        {{--                        $idLanguage = $language->idLanguage;--}}
        {{--                        $description = mb_ereg_replace("\r\n","\\n",$entries[$idLanguage]->description);--}}
        {{--                    @endphp--}}
        {{--                <hx-tab>{{$language->description}}</hx-tab>--}}
        {{--                @endforeach--}}
        {{--            </hx-tablist>--}}
        {{--            <hx-tabcontent>--}}
        {{--                @foreach($languages as $language)--}}
        {{--                    @php--}}
        {{--                        $idLanguage = $language->idLanguage;--}}
        {{--                        $description = mb_ereg_replace("\r\n","\\n",$entries[$idLanguage]->description);--}}
        {{--                    @endphp--}}
        {{--                <hx-tabpanel>--}}
        {{--                    <hx-div>--}}
        {{--                        <x-hidden-field--}}
        {{--                            id="idEntry[{{$idLanguage}}]"--}}
        {{--                            :value="$entries[$idLanguage]->idEntry"--}}
        {{--                        ></x-hidden-field>--}}
        {{--                        <x-text-field--}}
        {{--                            label="Name"--}}
        {{--                            id="name[{{$idLanguage}}]"--}}
        {{--                            :value="$entries[$idLanguage]->name"--}}
        {{--                        ></x-text-field>--}}
        {{--                        <x-multiline-field--}}
        {{--                            label="Definition"--}}
        {{--                            id="description[{{$idLanguage}}]"--}}
        {{--                            value="{!! $description !!}"--}}
        {{--                            rows="15"--}}
        {{--                        >--}}
        {{--                        </x-multiline-field>--}}
        {{--                    </hx-div>--}}
        {{--                </hx-tabpanel>--}}
        {{--                @endforeach--}}
        {{--            </hx-tabcontent>--}}
        {{--        </hx-tabset>--}}
        <script>
            $(function() {
                $("#frmEntries .item")
                    .tab();
            });
        </script>
    </x-slot:fields>
    <x-slot:buttons>
        <x-submit
            label="Save"
            hx-put="/entry"
        ></x-submit>
    </x-slot:buttons>
</x-form>
