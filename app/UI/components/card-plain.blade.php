<div {{ $attributes->merge(['class' => 'wt-card-plain']) }}>
    <div class="card-header">{!! $title !!}</div>
    <div class="card-body">
        {{$slot}}
    </div>
</div>
