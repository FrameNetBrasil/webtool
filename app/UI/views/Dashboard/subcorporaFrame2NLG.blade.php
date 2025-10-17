{{--<div class="dashboard-subtitle">{{__('dashboard.textualAnnotation')}}</div>--}}
{{--<div class="flex gap-2">--}}
{{--    <div class="dashboard-card  dashboard-card1">--}}
{{--        <div class="header">{{__('dashboard.annotatedSentences')}}</div>--}}
{{--        <div class="body">--}}
{{--            {{$frame2NLG['sentences']}}--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <div class="dashboard-card  dashboard-card3">--}}
{{--        <div class="header">{{__('dashboard.frames')}}</div>--}}
{{--        <div class="body">--}}
{{--            {{$frame2NLG['framesText']}}--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <div class="dashboard-card  dashboard-card4">--}}
{{--        <div class="header">{{__('dashboard.fes')}}</div>--}}
{{--        <div class="body">--}}
{{--            {{$frame2NLG['fesText']}}--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <div class="dashboard-card  dashboard-card5">--}}
{{--        <div class="header">{{__('dashboard.lus')}}</div>--}}
{{--        <div class="body">--}}
{{--            {{$frame2NLG['lusText']}}--}}
{{--        </div>--}}
{{--    </div>--}}
{{--    <div class="dashboard-card  dashboard-card6">--}}
{{--        <div class="header">{{__('dashboard.as')}}</div>--}}
{{--        <div class="body">--}}
{{--            {{$frame2NLG['asText']}}--}}
{{--        </div>--}}
{{--    </div>--}}
{{--</div>--}}
<div class="dashboard-subtitle">{{__('dashboard.videoAnnotation')}}</div>
<div class="flex gap-2">
    <div class="dashboard-card  dashboard-card2">
        <div class="header">{{__('dashboard.annotatedBBox')}}</div>
        <div class="body">
            {{$frame2NLG['bbox']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card3">
        <div class="header">{{__('dashboard.frames')}}</div>
        <div class="body">
            {{$frame2NLG['framesBBox']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card4">
        <div class="header">{{__('dashboard.fes')}}</div>
        <div class="body">
            {{$frame2NLG['fesBBox']}}
        </div>
    </div>
    <div class="dashboard-card  dashboard-card5">
        <div class="header">{{__('dashboard.cvs')}}</div>
        <div class="body">
            {{$frame2NLG['lusBBox']}}
        </div>
    </div>
</div>
<div class="dashboard-subtitle">{{__('dashboard.averages')}}</div>
<div class="flex gap-2">
{{--    <div class="dashboard-card  dashboard-card6">--}}
{{--        <div class="header">{{__('dashboard.avgSentence')}}</div>--}}
{{--        <div class="body">--}}
{{--            {{$frame2NLG['avgAS']}}--}}
{{--        </div>--}}
{{--        <div class="footer">--}}
{{--            {{__('dashboard.avgSentenceUL')}}--}}
{{--        </div>--}}
{{--    </div>--}}
    <div class="dashboard-card  dashboard-card7">
        <div class="header">{{__('dashboard.avgBBox')}}</div>
        <div class="body">
            {{$frame2NLG['avgDuration']}}
        </div>
        <div class="footer">
            {{__('dashboard.avgBBoxSeconds')}}
        </div>
    </div>
</div>
