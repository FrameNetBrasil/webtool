<?php

namespace App\Data\Annotation\StaticEvent;

use Spatie\LaravelData\Data;

class AnnotationCommentData extends Data
{
    public function __construct(
        public ?int    $idDocumentSentence = null,
        public ?string $comment = '',
        public string  $_token = '',
    )
    {
        $this->_token = csrf_token();
    }

}
