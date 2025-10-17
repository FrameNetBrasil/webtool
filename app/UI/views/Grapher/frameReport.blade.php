<h3>
    <x-element.frame name="{{$frame->name}}"></x-element.frame>
</h3>
<x-card title="Definition" class="frameReport__card frameReport__card--main">
    {!! str_replace('ex>','code>',nl2br($frame->description)) !!}
</x-card>
<x-card title="Frame Elements" class="frameReport__card frameReport__card--main">
    <table class="ui celled striped table" style="max-width:320px">
        <thead>
        <tr>
            <th>Core</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($fe['core'] as $feObj)
            <tr>
                <td class="collapsing" style="max-width:320px;word-break: break-all;white-space:normal;word-wrap:break-word;">
                    <div class="color_{{$feObj->idColor}}">{{$feObj->name}}</div>
                    <div class="pl-2">{!! $feObj->description !!}</div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if ($fe['core_unexpressed'])
        <table class="ui celled striped table" style="max-width:320px">
            <thead>
            <tr>
                <th>Core-Unexpressed</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($fe['core_unexpressed'] as $feObj)
                <tr>
                    <td class="collapsing" style="max-width:320px;word-break: break-all;white-space:normal;word-wrap:break-word;">
                        <div class="color_{{$feObj->idColor}}">{{$feObj->name}}</div>
                        <div class="pl-2">{!! $feObj->description !!}</div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
    @if ($fecoreset)
        <table class="ui celled striped table" style="max-width:320px">
            <thead>
            <tr>
                <th>FE Core set(s)</th>
            </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="collapsing" style="max-width:320px;word-break: break-all;white-space:normal;word-wrap:break-word;">
                        <div class="pl-2">{{$fecoreset}}</div>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif
    @if ($fe['peripheral'])
        <table class="ui celled striped table" style="max-width:320px">
            <thead>
            <tr>
                <th>Peripheral</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($fe['peripheral'] as $feObj)
                <tr>
                    <td class="collapsing" style="max-width:320px;word-break: break-all;white-space:normal;word-wrap:break-word;">
                        <div class="color_{{$feObj->idColor}}">{{$feObj->name}}</div>
                        <div class="pl-2">{!! $feObj->description !!}</div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
    @if ($fe['extra_thematic'])
        <table class="ui celled striped table" style="max-width:320px">
            <thead>
            <tr>
                <th>Extra-thematic</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($fe['extra_thematic'] as $feObj)
                <tr>
                    <td class="collapsing" style="max-width:320px;word-break: break-all;white-space:normal;word-wrap:break-word;">
                        <div class="color_{{$feObj->idColor}}">{{$feObj->name}}</div>
                        <div class="pl-2">{!! $feObj->description !!}</div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</x-card>
