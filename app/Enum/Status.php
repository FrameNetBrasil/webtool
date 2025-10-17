<?php

namespace App\Enum;

enum Status: string
{
    case PENDING = 'PENDING';
    case CREATED = 'CREATED';
    case DELETED = 'DELETED';
    case ACCEPTED = 'ACCEPTED';
    case UPDATED = 'UPDATED';
}
