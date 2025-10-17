<?php

namespace App\Enum;

enum AnnotationType: string
{
    case ANNOTATIONSET = 'annotationSet';
    case DYNAMICMODE = 'dynamicMode';
    case DEIXIS = 'deixis';
    case STATICBBOX = 'staticBBox';
    case STATICEVENT = 'staticEvent';
}
