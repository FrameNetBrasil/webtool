<div class="dashboard-subtitle">{{__('dashboard.imageAnnotation')}}</div>
<div class="d-flex gap-2">
    <div class="dashboard-card  dashboard-card1">
        <div class="header">{{__('dashboard.annotatedObjects')}}</div>
        <div class="body">
            {{$multi30kEntity['images']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card2">
        <div class="header">{{__('dashboard.annotatedBBox')}}</div>
        <div class="body">
            {{$multi30kEntity['bbox']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card3">
        <div class="header">{{__('dashboard.frames')}}</div>
        <div class="body">
            {{$multi30kEntity['framesImage']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card4">
        <div class="header">{{__('dashboard.fes')}}</div>
        <div class="body">
            {{$multi30kEntity['fesImage']}}
        </div>
    </div>
</div>
