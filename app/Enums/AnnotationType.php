<?php

namespace App\Enums;

enum AnnotationType: string
{
    case ANNOTATIONSET = 'annotationSet';
    case DYNAMICMODE = 'dynamicMode';
    case DEIXIS = 'deixis';
    case CANVAS = 'canvas';
    case STATICBBOX = 'staticBBox';
    case STATICEVENT = 'staticEvent';
}
