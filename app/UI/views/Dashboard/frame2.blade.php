<div class="dashboard-subtitle">{{__('dashboard.textualAnnotation')}}</div>
<div class="d-flex gap-2">
    <div class="dashboard-card  dashboard-card1">
        <div class="header">{{__('dashboard.annotatedSentences')}}</div>
        <div class="body">
            {{$annotation['sentences']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card3">
        <div class="header">{{__('dashboard.frames')}}</div>
        <div class="body">
            {{$annotation['framesText']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card4">
        <div class="header">{{__('dashboard.fes')}}</div>
        <div class="body">
            {{$annotation['fesText']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card5">
        <div class="header">{{__('dashboard.lus')}}</div>
        <div class="body">
            {{$annotation['lusText']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card6">
        <div class="header">{{__('dashboard.as')}}</div>
        <div class="body">
            {{$annotation['asText']}}
        </div>
    </div>
</div>
<div class="dashboard-subtitle">{{__('dashboard.videoAnnotation')}}</div>
<div class="d-flex gap-2">
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
<div class="d-flex gap-2">
    <div class="dashboard-card  dashboard-card6">
        <div class="header">{{__('dashboard.avgSentence')}}</div>
        <div class="body">
            {{$annotation['avgAS']}}
        </div>
        <div class="footer">
            {{__('dashboard.avgSentenceUL')}}
        </div>
    </div>
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
