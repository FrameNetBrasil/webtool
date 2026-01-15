<?php

namespace App\Http\Controllers\Sentence;

use App\Data\Sentence\SearchData;
use App\Data\Sentence\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\AnnotationSet;
use App\Services\Annotation\CorpusService;
use App\Services\AppService;
use App\Services\Sentence\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware("master")]
class ResourceController extends Controller
{
    #[Get(path: '/sentence')]
    public function index(SearchData $search)
    {
        $data = BrowseService::browseSentenceBySearch($search);
        return view('Sentence.browse', [
            'data' => $data,
        ]);
    }

    #[Post(path: '/sentence/search')]
    public function search(SearchData $search)
    {
        $data = BrowseService::browseSentenceBySearch($search);
        return view('Sentence.browse', [
            'data' => $data,
        ])->fragment('search');
    }

//    #[Get(path: '/sentence')]
//    public function browse()
//    {
//        $search = session('searchLexicon') ?? SearchData::from();
//        return view("Sentence.resource", [
//            'search' => $search
//        ]);
//    }
//
//    #[Get(path: '/sentence/grid/{fragment?}')]
//    #[Post(path: '/sentence/grid/{fragment?}')]
//    public function grid(SearchData $search, ?string $fragment = null)
//    {
//        $view = view("Sentence.grid", [
//            'search' => $search,
//            'sentences' => [],
//        ]);
//        return (is_null($fragment) ? $view : $view->fragment('search'));
//    }
//
    #[Get(path: '/sentence/new')]
    public function formSentenceNew()
    {
        return view("Sentence.formNew");
    }

    #[Get(path: '/sentence/{idSentence}')]
    public function sentence(int $idSentence)
    {
        $sentence = Criteria::byId("view_sentence","idSentence",$idSentence);
        return view("Sentence.edit", [
            'sentence' => $sentence,
        ]);
    }

    #[Get(path: '/sentence/{id}/formEdit')]
    public function formEdit(string $id)
    {
        $sentence = Criteria::byId("view_sentence","idSentence",$id);
        $as = Criteria::table("annotationset")
            ->where("idSentence", $id)
            ->all();
        return view("Sentence.formEdit",[
            'sentence' => $sentence,
            'hasAS' => !empty($as)
        ]);
    }

    #[Get(path: '/sentence/{id}/annotations')]
    public function annotations(string $id)
    {
        $data = CorpusService::getResourceDataByIdSentence($id, null, 'as');
        $data['annotationSets'] = AnnotationSet::getTargetsByIdSentence($id);
        return view("Sentence.annotations",$data);
    }

    #[Put(path: '/sentence')]
    public function updateSentence(UpdateData $data)
    {
        try {
            Criteria::table("sentence")
                ->where("idSentence", $data->idSentence)
                ->update([
                    'text' => $data->text
                ]);
            return $this->renderNotify("success", "Sentence updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/sentence/{idSentence}')]
    public function deleteSentence(string $idSentence)
    {
        try {
            Criteria::function("sentence_delete(?,?)", [$idSentence, AppService::getCurrentIdUser()]);
            $this->trigger('reload-gridSentence');
            return $this->renderNotify("success", "Sentence removed.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


}
