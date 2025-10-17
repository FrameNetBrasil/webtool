<div class="dashboard-subtitle">Health</div>
<div class="flex flex-row gap-x-2">
    <div class="card w-96 dashboard-card3">
        <div class="header">Distinct Frames</div>
        <div class="body">
            {{$data->mcgovern['hFrames']}}
        </div>
    </div>
    <div class="card w-96 dashboard-card5">
        <div class="header">Distinct LUs</div>
        <div class="body">
            {{$data->mcgovern['hLus']}}
        </div>
    </div>
</div>
<div class="dashboard-subtitle">Violence</div>
<div class="flex flex-row gap-x-2">
    <div class="card w-96 dashboard-card3">
        <div class="header">Distinct Frames</div>
        <div class="body">
            {{$data->mcgovern['vFrames']}}
        </div>
    </div>
    <div class="card w-96 dashboard-card5">
        <div class="header">Distinct LUs</div>
        <div class="body">
            {{$data->mcgovern['vLus']}}
        </div>
    </div>
</div>
