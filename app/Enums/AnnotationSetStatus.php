<?php

namespace App\Enums;

enum AnnotationSetStatus: string
{
    case UNANNOTATED = 'ast_unann';
    case PARTIAL = 'ast_partial';
    case COMPLETE = 'ast_complete';
    case ALTERNATIVE = 'ast_alternative';
}
