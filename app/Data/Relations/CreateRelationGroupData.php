<?php

namespace App\Data\Relations;

use App\Database\Criteria;
use App\Services\AppService;
use Spatie\LaravelData\Data;

class CreateRelationGroupData extends Data
{
    public function __construct(
        public ?string $nameEn = '',
        public string $_token = '',
    )
    {
    }
}
