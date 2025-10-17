<div class="tabs-component {{$id}}-context">
    <div
        class="ui {{$style}} menu"
        x-init="
            let {{$id}}_tabs = {!! Js::from($tabs) !!};
            $('.{{$id}}-context .menu .item').tab({
                onLoad: (tabPath, parameterArray, historyEvent) => {
                    let tab = '.' + tabPath;
                    htmx.ajax('GET', {{$id}}_tabs[tabPath].url, tab);
                }
            });
        "
    >
        @foreach($tabs as $idItem => $item)
            <a
                class="item"
                data-tab="{{$idItem}}"
            >
                @if(isset($item['icon']))
                    <x-dynamic-component :component="'icon::' . $item['icon']" />
                @endif
                {{$item['label']}}
            </a>
        @endforeach
    </div>
    @foreach($tabs as $idItem => $item)
        <div
            class="ui tab {{$idItem}}"
            data-tab="{{$idItem}}"
        >
            <div class="ui segment" style="height:80px">
                <div class="ui active inverted dimmer">
                    <div class="ui text loader">Loading</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
