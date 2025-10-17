<div class="wt-tabs flex flex-column h-full w-full" id="{{$id}}">
<div class="wt-tabs-tabs flex-none flex flex-row">
    @foreach ($tabs as $idTab => $tabName)
        <div class="wt-tabs-tabs-title {{ $idTab == $active ? 'activeTab' : '' }}" data-tab-value="#{{$idTab}}">{{$tabName}}</div>
    @endforeach
</div>
@foreach ($slots as $idSlot => $slot)
    <div class="flex-grow-1 wt-tabs-content {{$idSlot == $active ? 'active' : ''}}" id="{{$idSlot}}" data-tab-info>
        {{ $$slot }}
    </div>
@endforeach
</div>

<script type="text/javascript">
    // function to get each tab details
    const tabs = document.querySelectorAll('[data-tab-value]')
    const tabInfos = document.querySelectorAll('[data-tab-info]')
    tabs.forEach(tab => {
        tab.addEventListener('click', (x) => {
            @if($onSelect != '')
                {!! $onSelect !!}(tab.dataset.tabValue.substring(1))
            @endif
            const target = document.querySelector(tab.dataset.tabValue);
            tabInfos.forEach(tabInfo => {
                tabInfo.classList.remove('active')
            })
            tabs.forEach(tab => {
                tab.classList.remove('activeTab')
            })
            target.classList.add('active');
            x.target.classList.add('activeTab');
        })
    })
</script>
