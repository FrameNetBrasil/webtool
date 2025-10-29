<x-layout.index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['/domain','Domain/SemanticType'],['', 'Domain #' . $domain->idDomain]]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$domain->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$domain->idDomain}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Domain '{{$domain?->name}}'.`, '/domain/{{$domain->idDomain}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$domain->description}}
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="domainTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/domain/'.$domain->idDomain.'/formEdit'],
                            'semanticTypes' => ['id' => 'semanticTypes', 'label' => 'SemanticTypes', 'url' => '/domain/'.$domain->idDomain.'/semanticTypes']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-layout::footer></x-layout::footer>
    </div>
</x-layout.index>
