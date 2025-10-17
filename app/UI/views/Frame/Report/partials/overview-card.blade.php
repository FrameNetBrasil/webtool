{{--
    Frame Overview Card - Additional frame information
    Parameters:
    - $frame: Frame object
--}}
<div class="ui card fluid data-card overview-card">
    <div class="content">
        <div class="data-card-header">
            <div class="data-card-title">
                <div class="header">Frame Overview</div>
            </div>
        </div>
        <div class="description">
            <div class="frame-info">
                <p><strong>Frame:</strong> {{ $frame->name }}</p>
                @if(isset($frame->idFrameType) && $frame->idFrameType)
                    <p><strong>Type:</strong> {{ $frame->frameType ?? 'Standard' }}</p>
                @endif
                @if(isset($frame->idEntity) && $frame->idEntity)
                    <p><strong>Entity ID:</strong> {{ $frame->idEntity }}</p>
                @endif
            </div>
        </div>
    </div>
</div>