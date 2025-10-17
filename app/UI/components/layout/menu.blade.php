@php
    use Illuminate\Support\Facades\Auth;
    use App\Data\MenuData;
    use App\Services\AppService;

    $actions = config('webtool.actions');
    $isLogged = Auth::check();
    if ($isLogged) {
        $user = Auth::user();
        $userLevel = session('userLevel');
    }
    $currentLanguage = session('currentLanguage');
    $languages = config('webtool.user')[3]['language'][3];
    $profile = config('webtool.user')[3]['profile'][3];
    $hrefLogin = (env('APP_AUTH') == 'auth0') ? '/auth0Login' : '/';
@endphp
<div class="full height">
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
                    <div class="header">{!! $menuData->label !!}</div>
                    <div class="menu">
                        @foreach($menuData->items as $idItem => $item)
                            @php
                                $itemData = MenuData::from([
                                    'id' => $idItem . '_small',
                                    'label' => $item[0],
                                    'href' => $item[1],
                                    'group' => $item[2],
                                    'items' => $item[3]
                                ]);
                            @endphp
                            <a class="item" href="{{$itemData->href}}">{{$itemData->label}}</a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
</div>
