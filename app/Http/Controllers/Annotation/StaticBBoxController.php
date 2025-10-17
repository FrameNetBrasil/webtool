<?php

namespace App\Http\Controllers\Annotation;

use App\Data\Annotation\Browse\SearchData;
use App\Http\Controllers\Controller;
use App\Services\Annotation\BrowseService;
use App\Services\Annotation\ImageService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;


#[Middleware(name: 'auth')]
class StaticBBoxController extends Controller
{
    #[Get(path: '/annotation/staticBBox')]
    public function browse(SearchData $search)
    {
        $data = BrowseService::browseCorpusBySearch($search, [], "StaticBBoxAnnotation");

        return view('Annotation.browseDocuments', [
            'page' => "StaticBBox Annotation",
            'url' => "/annotation/staticBBox",
            'data' => $data,
            'taskGroupName' => 'StaticBBoxAnnotation'
        ]);
    }

    #[Get(path: '/annotation/staticBBox/sentences/{idDocument}')]
    public function gridSentences(int $idDocument)
    {
        $sentences = BrowseService::listSentences($idDocument);
        return view("Annotation.Image.Panes.sentences", [
            'sentences' => $sentences
        ]);
    }
    private function getData(int $idDocument, ?int $idStaticObject = null): array
    {
        return ImageService::getResourceData($idDocument, $idStaticObject, 'staticBBox');
    }

    #[Get(path: '/annotation/staticBBox/{idDocument}/{idStaticObject?}')]
    public function annotation(int|string $idDocument, ?int $idStaticObject = null)
    {
        $data = $this->getData($idDocument, $idStaticObject);

        return response()
            ->view('Annotation.Image.annotation', $data)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    #[Delete(path: '/annotation/staticBBox/{idDocument}/{idStaticObject}')]
    public function deleteObject(int $idDocument, int $idStaticObject)
    {
        try {
            ImageService::deleteObject($idStaticObject);

            return $this->redirect("/annotation/staticBBox/{$idDocument}");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }


}
