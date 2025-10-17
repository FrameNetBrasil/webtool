<?php

namespace App\Http\Controllers\Utils;

use App\Data\Corpus\SearchData;
use App\Data\Utils\ImportFullTextData;
use App\Http\Controllers\Controller;
use App\Repositories\Document;
use App\Services\UtilsService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class ImportFullTextController extends Controller
{
//    #[Get(path: '/utils/importFullText')]
//    public function resource()
//    {
//        return view("Utils.ImportFullText.resource");
//    }
//
//    #[Get(path: '/utils/importFullText/grid/{fragment?}')]
//    #[Post(path: '/utils/importFullText/grid/{fragment?}')]
//    public function grid(SearchData $search, ?string $fragment = null)
//    {
//        $view = view("Utils.ImportFullText.grid",[
//            'search' => $search,
//            'sentences' => [],
//        ]);
//        return (is_null($fragment) ? $view : $view->fragment('search'));
//    }

    #[Get(path: '/utils/importFullText/{id}')]
    public function formEdit(string $id)
    {
        return view("Utils.ImportFullText.formImportFullText",[
            'document' => Document::byId($id)
        ]);
    }

    #[Post(path: '/utils/importFullText')]
    public function update(ImportFullTextData $data)
    {
        try {
            debug($data);
            UtilsService::importFullText($data);
            return $this->renderNotify("success", "Text imported.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}
