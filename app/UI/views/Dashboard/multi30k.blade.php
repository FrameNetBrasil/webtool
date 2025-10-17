
<div class="dashboard-corpus">
    <div class="dashboard-title2">{{ __("dashboard.totals") }}</div>
    @include('Dashboard.subcorporaMulti30k')
</div>

<div class="dashboard-corpus">
    <div class="dashboard-title2">Entidades</div>
    @include('Dashboard.subcorporaMulti30kEntity')
</div>

<div class="dashboard-corpus">
    <div class="dashboard-title2">Eventos</div>
    @include('Dashboard.subcorporaMulti30kEvent')
</div>

@include('Dashboard.subcorporaMulti30kLome')

{{--@include('Dashboard.subcorporaMulti30kChart')--}}
