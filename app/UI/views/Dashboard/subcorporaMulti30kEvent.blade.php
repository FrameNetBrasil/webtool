<div class="dashboard-subtitle">{{__('dashboard.imageAnnotation')}}</div>
<div class="flex gap-2">
    <div class="dashboard-card  dashboard-card1">
        <div class="header">{{__('dashboard.annotatedObjects')}}</div>
        <div class="body">
            {{$multi30kEvent['images']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card2">
        <div class="header">{{__('dashboard.annotatedBBox')}}</div>
        <div class="body">
            {{$multi30kEvent['bbox']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card3">
        <div class="header">{{__('dashboard.frames')}}</div>
        <div class="body">
            {{$multi30kEvent['framesImage']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card4">
        <div class="header">{{__('dashboard.fes')}}</div>
        <div class="body">
            {{$multi30kEvent['fesImage']}}
        </div>
    </div>
</div>
