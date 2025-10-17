<div class="dashboard-subtitle">{{__('dashboard.boxesPerMonth')}}</div>
<div class="chart-container" style="position: static; height:40vh; width:80vw">
    <canvas id="boxesPerMonth"></canvas>
</div>
</div>

@php
$labels = [];
$values = [];
foreach($multi30k['chart'] as $c) {
    $labels[] = $c['m'];
    $values[] = $c['value'];
}
@endphp
<script type="application/javascript">
    document.addEventListener("DOMContentLoaded", function(e) {
        (async function() {
            const ctx = document.getElementById('boxesPerMonth');
            const labels = {{ Js::from($labels) }};
            const data = {
                labels: labels,
                maintainAspectRatio: false,
                datasets: [{
                    label: '{{__('dashboard.boxesPerMonth')}}',
                    data: {{ Js::from($values) }},
                    fill: false,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            };
            new window.Chart(ctx, {
                type: 'line',
                data: data
            });
        })();
    });
</script>
