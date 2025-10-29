<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['/domain','Domain/SemanticType'],['', 'SemanticType #' . $semanticType->idSemanticType]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$semanticType->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$semanticType->idSemanticType}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing SemanticType '{{$semanticType?->name}}'.`, '/semanticType/{{$semanticType->idSemanticType}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$semanticType->description}}
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="semanticTypeTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/semanticType/'.$semanticType->idSemanticType.'/formEdit'],
                            'translations' => ['id' => 'translations', 'label' => 'Translations', 'url' => '/semanticType/'.$semanticType->idSemanticType.'/translations'],
                            'subTypes' => ['id' => 'subTypes', 'label' => 'SubTypes', 'url' => '/semanticType/'.$semanticType->idSemanticType.'/subTypes'],
                            'relations' => ['id' => 'relations', 'label' => 'Relations', 'url' => '/semanticType/'.$semanticType->idSemanticType.'/relations']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
