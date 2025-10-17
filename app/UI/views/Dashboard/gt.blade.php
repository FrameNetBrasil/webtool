@php
    $language = session('currentLanguage')->language;
@endphp
<x-wt::indexLayout :data="$data">
    <div class="w-full">
        <div class="dashboard-subtitle">GT 1</div>
        <div style="margin-bottom: 8px">
            <div class="flex flex-row gap-x-2">
                <div class="card w-96 dashboard-card3">
                    <div class="header">Sentences</div>
                    <div class="body">
                        {{$data->gt['gt1DocSen']}}
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-row gap-x-2">
            @foreach($data->gt['gt1DocFrm'] as $frm)
                <div class="card w-96 dashboard-card1">
                    <div class="header">{{$frm->name}}</div>
                    <div class="body">
                        {{$frm->f}}
                    </div>
                </div>
            @endforeach
        </div>
        <div class="dashboard-subtitle">GT 2</div>
        <div style="margin-bottom: 8px">
            <div class="flex flex-row gap-x-2">
                <div class="card w-96 dashboard-card3">
                    <div class="header">Sentences</div>
                    <div class="body">
                        {{$data->gt['gt2DocSen']}}
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-row gap-x-2">
            @foreach($data->gt['gt2DocFrm'] as $frm)
                <div class="card w-96 dashboard-card1">
                    <div class="header">{{$frm->name}}</div>
                    <div class="body">
                        {{$frm->f}}
                    </div>
                </div>
            @endforeach
        </div>
        <div class="dashboard-subtitle">GT 3</div>
        <div style="margin-bottom: 8px">
            <div class="flex flex-row gap-x-2">
                <div class="card w-96 dashboard-card3">
                    <div class="header">Sentences</div>
                    <div class="body">
                        {{$data->gt['gt3DocSen']}}
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-row gap-x-2">
            @foreach($data->gt['gt3DocFrm'] as $frm)
                <div class="card w-96 dashboard-card1">
                    <div class="header">{{$frm->name}}</div>
                    <div class="body">
                        {{$frm->f}}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-wt::indexLayout>