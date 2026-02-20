<?php

namespace App\Enums;

enum Status: string
{
    case PENDING = 'PENDING';
    case CREATED = 'CREATED';
    case DELETED = 'DELETED';
    case ACCEPTED = 'ACCEPTED';
    case UPDATED = 'UPDATED';
    case ARCHIVED = 'ARCHIVED';

}
