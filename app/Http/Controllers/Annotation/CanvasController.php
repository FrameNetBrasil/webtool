<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Browse\SearchData;
use App\Http\Controllers\Controller;
use App\Services\Annotation\BrowseService;
use App\Services\Annotation\VideoService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;


#[Middleware(name: 'auth')]
class CanvasController extends Controller
{
    #[Get(path: '/annotation/canvas')]
    public function browse(SearchData $search)
    {
        $data = BrowseService::browseCorpusBySearch($search, [], "CanvasAnnotation");

        return view('Annotation.browseDocuments', [
            'page' => "Canvas Annotation",
            'url' => "/annotation/canvas",
            'data' => $data,
            'taskGroupName' => 'CanvasAnnotation'
        ]);
    }

    private function getData(int $idDocument, ?int $idDynamicObject = null): array
    {
        return VideoService::getResourceData($idDocument, $idDynamicObject, 'canvas');
    }

    #[Get(path: '/annotation/canvas/{idDocument}/{idDynamicObject?}')]
    public function annotation(int|string $idDocument, ?int $idDynamicObject = null)
    {
        $data = $this->getData($idDocument, $idDynamicObject);

        return response()
            ->view('Annotation.Video.annotation', $data)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    #[Delete(path: '/annotation/canvas/{idDocument}/{idDynamicObject}')]
    public function deleteObject(int $idDocument, int $idDynamicObject)
    {
        try {
            VideoService::deleteObject($idDynamicObject);

            return $this->redirect("/annotation/canvas/{$idDocument}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

}
