<x-layout::index>
    <div class="app-layout">
        <x-partial::header></x-partial::header>
        <x-partial::breadcrumb
            :sections="[['/','Home'],['/manager','Manager'],['/user','Group/User'],['', 'User #' . $user->idUser]]"
        ></x-partial::breadcrumb>
        <main class="app-main">
            <div class="ui container page-edit">
                <div class="page-header-object">
                    <div class="page-object">
                        <div class="page-object-name">
                            <span class="color_user">{{$user->login}}</span>
                        </div>
                        <div class="page-object-data">
                            <div class="ui label wt-tag-id">
                                #{{$user->idUser}}
                            </div>
                            <button
                                class="ui danger button"
                                x-data
                                @click.prevent="messenger.confirmDelete(`Removing User '{{$user?->login}}'.`, '/user/{{$user->idUser}}')"
                            >Delete</button>
                        </div>
                    </div>
                    <div class="page-subtitle">
                        {{$user->email}} [{{$user->name}}]
                    </div>
                </div>

                <div class="page-content">
                    <x-ui::tabs
                        id="userTabs"
                        style="secondary pointing"
                        :tabs="[
                            'edit' => ['id' => 'edit', 'label' => 'Edit', 'url' => '/user/'.$user->idUser.'/formEdit'],
                            'groups' => ['id' => 'groups', 'label' => 'Groups', 'url' => '/user/'.$user->idUser.'/groups']
                        ]"
                        defaultTab="edit"
                    />
                </div>
            </div>
        </main>
        <x-partial::footer></x-partial::footer>
    </div>
</x-layout::index>
