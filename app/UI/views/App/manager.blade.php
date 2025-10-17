@php
    use App\Services\AppService;

    $options = [
        'project' => ['Project/Dataset', '/project','', 'MANAGER','ui::icon.frame'],
        'task' => ['Task/User', '/task', '','MANAGER','ui::icon.frame'],
        'user' => ['Group/User', '/user','', 'ADMIN','ui::icon.frame'],
        'document' => ['Corpus/Document','/corpus','', 'ADMIN','ui::icon.domain'],
        'video' => ['Video/Document', '/video','', 'ADMIN','ui::icon.frame'],
        'image' => ['Image/Document', '/image','', 'ADMIN','ui::icon.frame'],
        'semantictype' => ['Domain/SemanticType','/semanticType','', 'ADMIN','ui::icon.frame'],
        'layer' => ['Layer/GenericLabel', '/layers','', 'ADMIN','ui::icon.frame'],
        'relations' => ['Relations', '/relations','', 'ADMIN','ui::icon.frame'],
        'importfulltext' => ['Import FullText', '/utils/importFullText', '','MANAGER','ui::icon.frame'],
        'aisuggestions' => ['LU AI Suggestions', '/lu/aiSuggestion','', 'ADMIN','ui::icon.lu'],
    ];

    $groups = [
        'manager' => ['title' => "Project/User", "pages" => ['project','task','user']],
        'document' => ['title' => "Document", "pages" => ['document','video','image']],
        'table' => ['title' => "Tables", "pages" => ['semantictype','layer','relations']],
        'utils' => ['title' => "Utils", "pages" => ['importfulltext']],
        'data' => ['title' => "Data", "pages" => ['aisuggestions']],
    ];


@endphp

<x-layout::index>
    <div class="app-layout minimal">
        <x-layout::header></x-layout::header>
        <x-layout::breadcrumb
            :sections="[['/','Home'],['','Manager']]"
        ></x-layout::breadcrumb>
        <main class="app-main">
            <div class="ui container">
                <div class="page-header">
                    <div class="page-header-content">
                        <div class="page-title">
                            Manager
                        </div>
                    </div>
                </div>
                <div class="page-content grid-page">
                    @foreach($groups as $group)
                        <div class="ui fluid card">
                            <div class="content  bg-gray-200">
                                <div class="header">
                                    {{$group['title']}}
                                </div>
                            </div>
                            <div class="content">
                                <div class="card-grid dense">
                                    @foreach($group['pages'] as $group)
                                        @php
                                            $item = $options[$group];
                                        @endphp
                                        @if (AppService::checkAccess($item[3]))
                                            <a
                                                class="ui card option-card"
                                                data-category="{{$group}}"
                                                href="{{$item[1]}}"
                                                hx-boost="true"
                                            >
                                                <div class="content">
                                                    <div class="header">
                                                        <x-dynamic-component :component="$item[4]"/>
                                                        {{$item[0]}}
                                                    </div>
                                                    <div class="description">
                                                        {{$item[2]}}
                                                    </div>
                                                </div>
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </main>
    </div>
</x-layout::index>
