@if($display == "domainTableContainer")
    <div class="grid h-full">
        <div id="domainTableContainer" class="col md:col-6">
            @include("Frame.treeGroup")
        </div>
        <div id="frameTableContainer" class="col md:col-6">
            @include("Frame.treeFrame")
        </div>
{{--        <div id="feluTableContainer" class="hxCol hxSpan-12 hxSpan-4-md">--}}
{{--            @include("Frame.treeFELU")--}}
{{--        </div>--}}
    </div>
@endif
@if($display == "frameTableContainer")
    <div class="grid grid-nogutter h-full">
        <div id="frameTableContainer" class="col">
            @include("Frame.treeFrame")
        </div>
{{--        <div id="feluTableContainer" class="hxCol hxSpan-6">--}}
{{--            @include("Frame.treeFELU")--}}
{{--        </div>--}}
    </div>
@endif
{{--@if($display == "feluTableContainer")--}}
{{--    @if($search->group != '')--}}
{{--        <div id="feluTableContainer" class="hxCol hxSpan-12 hxSpan-4-md">--}}
{{--            @include("Frame.treeFELU")--}}
{{--        </div>--}}
{{--    @else--}}
{{--        <div id="feluTableContainer" class="hxCol hxSpan-6">--}}
{{--            @include("Frame.treeFELU")--}}
{{--        </div>--}}
{{--    @endif--}}
{{--@endif--}}
@if($display == "luTableContainer")
    <div class="grid h-full">
        <div id="feluTableContainer" class="col">
            @include("Frame.treeFELU")
        </div>
    </div>
@endif
