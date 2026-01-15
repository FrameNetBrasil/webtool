{{--Layout for edit a specific record --}}
{{--Goal: Template for CRUD operations for a object --}}
<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home']]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    {{-- Header area with information of object --}}
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$object->name}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$object->id}}
                            </div>
                            {{-- Button for deletion --}}
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing Object '{{$object->name}}'.`, '/url/for/object/{{$object->id}}')"
                            >Delete
                            </button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$object->description ?? ''}}
                    </div>
                </div>

                <div class="page-content">
                    {{-- Main menu for object operations - presented as tabs --}}
                    <x-ui::tabs
                        id="objectTabs"
                        style="secondary pointing"
                        :tabs="[
                            'op1' => ['id' => 'op1', 'label' => 'Operation 1', 'url' => '/url/for/object/'.$object->id.'/operation1'],
                            'op2' => ['id' => 'op2', 'label' => 'Operation 2', 'url' => '/url/for/object/'.$object->id.'/operation2'],
                            'op3' => ['id' => 'op3', 'label' => 'Operation 3', 'url' => '/url/for/object/'.$object->id.'/operation3'],
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
