@use(\App\Enum\AnnotationSetStatus)

<div id="annotationSetStatus">
    @if($annotationSetStatus->entry == AnnotationSetStatus::COMPLETE->value)
        <div class="ui success message">
            <div class="header">
                FEs Core annotated.
            </div>
        </div>
    @endif
    @if($annotationSetStatus->entry == AnnotationSetStatus::PARTIAL->value)
        <div class="ui warning message">
            <div class="header">
                FEs Core partially annotated.
            </div>
        </div>
    @endif
    @if($annotationSetStatus->entry == AnnotationSetStatus::UNANNOTATED->value)
        <div class="ui error message">
            <div class="header">
                No annotation for FEs Core.
            </div>
        </div>
    @endif
</div>

