@use("Carbon\Carbon")
<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/structure','Structure'],['/parser','Parser'],['/parser/grammar','Grammar'],['',$grammar?->name]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$grammar->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label color_id">
                                #{{$grammar->idGrammarGraph}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(
                    `Removing Grammar Graph '{{ $grammar->name }}' will also delete all {{ $grammar->constructionCount }} associated constructions.`,
                    '/parser/grammar/{{ $grammar->idGrammarGraph }}')"
                            >Delete
                            </button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$grammar->description ?? ''}}
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="grammarMenu"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/parser/grammar/'.$grammar->idGrammarGraph.'/formEdit'],
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
