<?php

namespace App\Data\Annotation\Comment;

use App\Services\AppService;
use Carbon\Carbon;
use Spatie\LaravelData\Data;

class CommentData extends Data
{
    public function __construct(
        public ?int   $idAnnotationComment = null,
        public ?int   $idObject = null,
//        public ?int   $idDynamicObject = null,
//        public ?int   $idStaticObject = null,
//        public ?int   $idAnnotationSet = null,
        public ?int   $order = null,
        public ?int   $idDocument = null,
        public ?string $comment = '',
        public ?string $annotationType = '',
        public ?string $type = '',
        public ?int $idUser = null,
        public ?string $createdAt = '',
        public ?string $updatedAt = '',
        public string $_token = '',
    )
    {
//        if (($this->annotationType == 'dynamicMode') || ($this->annotationType == 'deixis')) {
//            $this->idDynamicObject = $this->idObject;
//            $this->type = 'video';
//        } elseif ($this->annotationType == 'staticBBox') {
//            $this->idStaticObject = $this->idObject;
//            $this->type = 'image';
//        } else if ($this->annotationType == 'text') {
//            $this->idAnnotationSet = $this->idObject;
//            $this->type = 'text';
//        }
        $this->idUser = AppService::getCurrentIdUser();
        if ($this->createdAt == '') {
            $this->createdAt = Carbon::now();
        }
        $this->updatedAt = Carbon::now();
        $this->_token = csrf_token();
    }

}
