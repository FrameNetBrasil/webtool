<div class="ui card w-full">
    <div class="content">
        <div class="header">Import Result</div>
    </div>
    <div class="content">
        <div class="ui statistics">
            <div class="statistic">
                <div class="value">{{ $result['total'] }}</div>
                <div class="label">Total</div>
            </div>
            <div class="green statistic">
                <div class="value">{{ $result['imported'] }}</div>
                <div class="label">Imported</div>
            </div>
            <div class="yellow statistic">
                <div class="value">{{ $result['skipped'] }}</div>
                <div class="label">Skipped</div>
            </div>
            <div class="red statistic">
                <div class="value">{{ count($result['errors']) }}</div>
                <div class="label">Errors</div>
            </div>
        </div>

        @if(count($result['errors']) > 0)
            <div class="ui error message">
                <div class="header">Errors</div>
                <ul class="list">
                    @foreach($result['errors'] as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($result['imported'] > 0)
            <div class="ui success message">
                Successfully imported {{ $result['imported'] }} construction(s).
            </div>
        @endif
    </div>
</div>
