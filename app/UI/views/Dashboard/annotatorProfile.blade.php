@php
    //  #688bc9; #a55394; #407e42; #e9a122; #5f479e; #85c04c; #54c5af; #e33357;
    //$ageLabels = __('dashboard.profileAgeLabels');
//    $schoolingLabels = __('dashboard.profileSchoolingLabels');
//    $ethnicityLabels = __('dashboard.profileEthnicityLabels');
    //$genderLabels = __('dashboard.profileGenderLabels');

    $ageLabels = [];
    $ageCounts = [];
    foreach($profile['ageGroups'] as $ageGroup) {
        $ageLabels[] = $ageGroup->age_group;
        $ageCounts[] = $ageGroup->count;
    }
    $schoolingLabels = [];
    $schoolingCounts = [];
    foreach($profile['schoolGroups'] as $schoolGroup) {
        $schoolingLabels[] = __('dashboard.profileSchoolingLabels.'.$schoolGroup->escolaridade);
        $schoolingCounts[] = $schoolGroup->count;
    }
    $ethnicityLabels = [];
    $ethnicityCounts = [];
    foreach($profile['ethnicityGroups'] as $ethnicityGroup) {
        $ethnicityLabels[] = __('dashboard.profileEthnicityLabels.'.$ethnicityGroup->etnia);
        $ethnicityCounts[] = $ethnicityGroup->count;
    }
    $genderLabels = [];
    $genderCounts = [];
    foreach($profile['genderGroups'] as $genderGroup) {
        $genderLabels[] = __('dashboard.profileGenderLabels.'.$genderGroup->gender);
        $genderCounts[] = $genderGroup->count;
    }

@endphp
<div class="d-flex gap-2 flex-wrap">
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
    $(function() {
        (async function() {
            const ctx = document.getElementById("profileAge");
            const data = {
                labels: {{ Js::from($ageLabels) }},
                datasets: [{
                    //data: [4, 41, 15, 6, 5],
                    data: {{ Js::from($ageCounts) }},
                    backgroundColor: ["#688bc9", "#a55394", "#407e42", "#e9a122", "#5f479e"],
                    hoverOffset: 4
                }]
            };
            new window.Chart(ctx, {
                type: "doughnut",
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
            const ctx = document.getElementById("profileSchooling");
            const data = {
                labels: {{ Js::from($schoolingLabels) }},
                datasets: [{
                    //data: [7, 9, 16, 39],
                    data: {{ Js::from($schoolingCounts) }},
                    backgroundColor: ["#688bc9", "#a55394", "#407e42", "#e9a122","#5f479e","#85c04c","#54c5af"],
                    hoverOffset: 4
                }]
            };
            new window.Chart(ctx, {
                type: "doughnut",
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
            const ctx = document.getElementById("profileEthnicity");
            const data = {
                labels: {{ Js::from($ethnicityLabels) }},
                datasets: [{
                    //data: [49, 1, 5, 15, 3],
                    data: {{ Js::from($ethnicityCounts) }},
                    backgroundColor: ["#688bc9", "#a55394", "#407e42", "#e9a122", "#5f479e"],
                    hoverOffset: 4
                }]
            };
            new window.Chart(ctx, {
                type: "doughnut",
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
            const ctx = document.getElementById("profileGender");
            const data = {
                labels: {{ Js::from($genderLabels) }},
                datasets: [{
                    //data: [24, 45, 1, 1],
                    data: {{ Js::from($genderCounts) }},
                    backgroundColor: ["#688bc9", "#a55394", "#407e42", "#e9a122", "#5f479e"],
                    hoverOffset: 4
                }]
            };
            new window.Chart(ctx, {
                type: "doughnut",
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
