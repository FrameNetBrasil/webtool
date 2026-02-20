<?php

namespace App\Enums;

enum SearchOptions: string
{
    case EXACT = 'Exact';
    case STARTSWITH = 'StartsWith';
    case CONTAINS = 'Contains';
}
