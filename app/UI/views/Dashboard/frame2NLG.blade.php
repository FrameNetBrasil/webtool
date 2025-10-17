<div class="dashboard-subtitle">{{__('dashboard.videoAnnotation')}}</div>
<div class="flex gap-2">
    <div class="dashboard-card  dashboard-card2">
        <div class="header">{{__('dashboard.annotatedBBox')}}</div>
        <div class="body">
            {{$annotation['bbox']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card3">
        <div class="header">{{__('dashboard.frames')}}</div>
        <div class="body">
            {{$annotation['framesBBox']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card4">
        <div class="header">{{__('dashboard.fes')}}</div>
        <div class="body">
            {{$annotation['fesBBox']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card5">
        <div class="header">{{__('dashboard.cvs')}}</div>
        <div class="body">
            {{$annotation['lusBBox']}}
        </div>
    </div>
</div>
<div class="dashboard-subtitle">{{__('dashboard.averages')}}</div>
<div class="flex gap-2">
    <div class="dashboard-card  dashboard-card7">
        <div class="header">{{__('dashboard.avgBBox')}}</div>
        <div class="body">
            {{$annotation['avgDuration']}}
        </div>
        <div class="footer">
            {{__('dashboard.avgBBoxSeconds')}}
        </div>
    </div>
</div>
