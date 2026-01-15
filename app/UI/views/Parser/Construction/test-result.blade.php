<div class="ui card w-full">
    <div class="content">
        <div class="header">Pattern Test Result</div>
    </div>
    <div class="content">
        @if($result['success'])
            <div class="ui success message">
                <div class="header">Pattern Valid</div>
                <p>{{ $result['message'] ?? 'Pattern compiled successfully' }}</p>
            </div>

            <div class="field">
                <label>Tested Pattern</label>
                <code>{{ $result['pattern'] }}</code>
            </div>

            <div class="field">
                <label>Test Sentence</label>
                <div class="ui segment">{{ $result['sentence'] }}</div>
            </div>

            @if(isset($result['matches']) && count($result['matches']) > 0)
                <div class="field">
                    <label>Matches Found</label>
                    <div class="ui list">
                        @foreach($result['matches'] as $match)
                            <div class="item">
                                <i class="check green icon"></i>
                                <div class="content">{{ $match }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="ui warning message">
                    <div class="header">No Matches</div>
                    <p>The pattern compiled successfully but did not match the test sentence.</p>
                </div>
            @endif
        @else
            <div class="ui error message">
                <div class="header">Pattern Error</div>
                <p>{{ $result['error'] }}</p>
            </div>
        @endif
    </div>
</div>
