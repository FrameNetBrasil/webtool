<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Comment\CommentData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\CommentService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware(name: 'auth')]
class CommentController extends Controller
{
//    #[Get(path: '/annotation/comment/form')]
//    public function getFormComment(CommentData $data)
//    {
//        $object = CommentService::getComment($data);
//        // Note: object can be null for new comments, which is handled by the view
//
//        return view('Annotation.Comment.formComment', [
//            'idDocument' => $data->idDocument,
//            'order' => $data->order,
//            'object' => $object,
//        ]);
//    }

    #[Post(path: '/annotation/comment/update')]
    public function updateComment(CommentData $data)
    {
        try {
            $comment = CommentService::updateComment($data);
            $this->notify('success', 'Comment registered.');
            return view("Annotation.Comment.formComment", [
                'comment' => $comment
            ]);
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/annotation/comment/{idAnnotationComment}')]
    public function deleteComment(int $idAnnotationComment)
    {
        try {
            $comment = Criteria::byId("view_annotation_comment","idAnnotationComment", $idAnnotationComment);
            CommentService::deleteComment($idAnnotationComment);
            $emptyComment = CommentService::getComment($comment->idObject, $comment->idDocument, $comment->annotationType);
            $this->notify('success', 'Comment removed.');
            return view("Annotation.Comment.formComment", [
                'comment' => $emptyComment
            ]);
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

}
