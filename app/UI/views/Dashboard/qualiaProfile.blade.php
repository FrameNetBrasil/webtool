@php
//  #688bc9; #a55394; #407e42; #e9a122; #5f479e; #85c04c; #54c5af; #e33357;
$qualiaLabels = __('dashboard.qualiaLabels');
$qualiaData = $data->mcgovern['qualiaData'];

@endphp
<div class="flex flex-row gap-x-2 flex-wrap">
<div class="chart-container" style="position: relative; width:500px">
    <canvas id="profileQualia"></canvas>
</div>
</div>
<script type="application/javascript">
    document.addEventListener("DOMContentLoaded", function(e) {
        (async function() {
            const ctx = document.getElementById('profileQualia');
            const data = {
                labels: {{Js::from($qualiaLabels) }},
                datasets: [{
                    data: {{Js::from($qualiaData) }},
                    backgroundColor: [
                        "#b1c4a3",
                        "#c44d91",
                        "#a07500",
                        "#00c56e",
                        "#74de47",
                        "#a6ba58",
                        "#0040ad",
                        "#ffe31e",
                        "#a291c6",
                        "#ff834a",
                        "#00a6cb",
                        "#c83b3e",
                        "#9d5e4e",
                        "#502e5d",
                        "#006142",
                    ],
                    hoverOffset: 4
                }]
            };
            new window.Chart(ctx, {
                type: 'doughnut',
                data: data,
                options: {
                    plugins: {
                        title: {
                            display: true,
                            text: '{{__('dashboard.profileQualia')}}'
                        }
                    }
                }
            });
        })();
    });
</script>
