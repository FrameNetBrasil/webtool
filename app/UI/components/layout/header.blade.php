@php
    use Illuminate\Support\Facades\Auth;
    use App\Data\MenuData;
    use App\Services\AppService;

    $actions = [
        'report' => ['Report', '/report', '', []],
        'grapher' => ['Grapher', '/grapher', '', []],
        'annotation' => ['Annotation', '/annotation', 'MASTER', []],
        'structure' => ['Structure', '/structure', 'MASTER', []],
        'manager' => ['Manager', '/manager', 'MANAGER', []],
//        'utils' => ['Utils', '/utils', 'ADMIN', []],
    ];
    $isLogged = Auth::check();
    if ($isLogged) {
        $user = Auth::user();
        $userLevel = session('userLevel');
    }
    $currentLanguage = session('currentLanguage');
    $languages = config('webtool.user')[3]['language'][3];
    $profile = config('webtool.user')[3]['profile'][3];
@endphp

<div class="app-header">
    <div class="header-left">
        <div class="brand">
            <div class="logo">
                <a href="/" hx-boost="true">
                    <img src="/images/fnbr_logo_header_alpha_white.png" height="24"/>
                </a>
            </div>
            <div class="title">
                <a href="/" hx-boost="true">
                    <span>{!! config('webtool.headerTitle') !!}</span>
                </a>
            </div>
        </div>
        <div class="menu">
            @foreach($actions as $id => $action)
                @php
                    $menuData = MenuData::from([
                        'id' => $id . '_small',
                        'label' => $action[0],
                        'href' => $action[1],
                        'group' => $action[2],
                        'items' => $action[3]
                    ]);
                @endphp
                @if (AppService::checkAccess($menuData->group))
                    <div class="item">
                        <a href="{{$menuData->href}}" hx-boost="true">
                            {!! $menuData->label !!}
                        </a>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
    <div class="header-center">
    </div>

    <div class="header-right">
        <div
            class="pr-3"
        >
            <form
                hx-post="/report/frame_lu/search"
                hx-push-url="true"
                hx-target="body"
            >
                <div class="item">
                    <div class="ui inverted left icon input">
                        <i class="search icon"></i>
                        <input
                            type="search"
                            name="frame"
                            placeholder="Search Frame/LU"
                        >
                    </div>
                </div>
            </form>
        </div>
        <div
            x-init="$($el).dropdown()"
            class="ui dropdown item "
            tabindex="0"
        >
            {!! $currentLanguage->description !!}<i class="dropdown icon"></i>
            <div class="menu" tabindex="-1">
                @foreach($languages as $language)
                    <div class="item" hx-get="{{$language[1]}}">{{$language[0]}}</div>
                @endforeach
            </div>
        </div>

        @if($isLogged)
            <div x-init="$($el).dropdown()"
                 class="ui dropdown item"
            >
                <div class="ui teal circular label">{!! strtoupper($user->email[0]) !!}</div>
                <div class="menu">
                    <div class="item">
                        {{$user->email}}
                    </div>
                    <div class="item">
                        Level: {{$userLevel}}
                    </div>
                    <div class="item">
                        ID: #{{$user->idUser}}
                    </div>
                    <div class="divider"></div>
                    <a class="item" href="/user/profile">My profile</a>
                    <div class="divider"></div>
                    <a class="item" href="/logout">
                        Logout
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
