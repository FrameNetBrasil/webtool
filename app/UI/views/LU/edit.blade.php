<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/frame','Frames'],['/frame/' . $lu->frame->idFrame,$lu->frame->name],['',$lu->frame->name.'.'.$lu?->name]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <x-ui::element.lu name="{{$lu->name}}"></x-ui::element.lu>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$lu->idLU}}
                            </div>
                            <div>
                                <x-combobox.lu-frame
                                    id="idLUFrame"
                                    :idFrame="$lu->frame->idFrame"
                                    :value="'Change LU'"
                                ></x-combobox.lu-frame>
                                <script>
                                    $(function () {
                                        $('#idLUFrame_dropdown').dropdown({
                                            onChange: (value) => {
                                                window.location.href = `/lu/${value}/edit`;
                                            }
                                        });
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                    <div class="page-subtitle">

                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="luTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/lu/'.$lu->idLU.'/formEdit'],
                            'qualia' => ['id' => 'qualia', 'label' => 'Qualia', 'url' => '/lu/'.$lu->idLU.'/qualia'],
                            'constraints' => ['id' => 'constraints', 'label' => 'Constraints', 'url' => '/lu/'.$lu->idLU.'/constraints'],
                            'semanticTypes' => ['id' => 'semanticTypes', 'label' => 'SemanticTypes', 'url' => '/lu/'.$lu->idLU.'/semanticTypes']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
