@php
//  #688bc9; #a55394; #407e42; #e9a122; #5f479e; #85c04c; #54c5af; #e33357;
$ageLabels = __('dashboard.profileAgeLabels');
$schoolingLabels = __('dashboard.profileSchoolingLabels');
$ethnicityLabels = __('dashboard.profileEthnicityLabels');
$genderLabels = __('dashboard.profileGenderLabels');

@endphp
<div class="flex flex-row gap-x-2 flex-wrap">
<div class="chart-container" style="position: relative; height:40vh; width:360px">
    <canvas id="profileAge"></canvas>
</div>
<div class="chart-container" style="position: relative; height:40vh; width:360px">
    <canvas id="profileSchooling"></canvas>
</div>
<div class="chart-container" style="position: relative; height:40vh; width:360px">
    <canvas id="profileEthnicity"></canvas>
</div>
<div class="chart-container" style="position: relative; height:40vh; width:360px">
    <canvas id="profileGender"></canvas>
</div>
</div>
<script type="application/javascript">
    document.addEventListener("DOMContentLoaded", function(e) {
        (async function() {
            const ctx = document.getElementById('profileAge');
            const data = {
                labels: {{ Js::from($ageLabels) }},
                datasets: [{
                    data: [4, 41, 15, 6, 5],
                    backgroundColor: ['#688bc9','#a55394','#407e42','#e9a122','#5f479e'],
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
                            text: '{{__('dashboard.profileAge')}}'
                        }
                    }
                }
            });
        })();
        (async function() {
            const ctx = document.getElementById('profileSchooling');
            const data = {
                labels: {{ Js::from($schoolingLabels) }},
                datasets: [{
                    data: [7, 9, 16, 39],
                    backgroundColor: ['#688bc9','#a55394','#407e42','#e9a122'],
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
                            text: '{{__('dashboard.profileSchooling')}}'
                        }
                    }
                }
            });
        })();
        (async function() {
            const ctx = document.getElementById('profileEthnicity');
            const data = {
                labels: {{ Js::from($ethnicityLabels) }},
                datasets: [{
                    data: [49, 1, 5, 15, 3],
                    backgroundColor: ['#688bc9','#a55394','#407e42','#e9a122','#5f479e'],
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
                            text: '{{__('dashboard.profileEthnicity')}}'
                        }
                    }
                }
            });
        })();
        (async function() {
            const ctx = document.getElementById('profileGender');
            const data = {
                labels: {{ Js::from($genderLabels) }},
                datasets: [{
                    data: [24, 45, 1, 1],
                    backgroundColor: ['#688bc9','#a55394','#407e42','#e9a122'],
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
                            text: '{{__('dashboard.profileGender')}}'
                        }
                    }
                }
            });
        })();
    });
</script>
