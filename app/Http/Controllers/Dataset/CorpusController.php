<?php

namespace App\Http\Controllers\Dataset;

use App\Data\Dataset\CorpusData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class CorpusController extends Controller
{
    #[Get(path: '/dataset/{id}/corpus')]
    public function corpus(int $id)
    {
        return view("Dataset.corpus", [
            'idDataset' => $id
        ]);
    }

    #[Get(path: '/dataset/{id}/corpus/formNew')]
    public function corpusFormNew(int $id)
    {
        return view("Dataset.corpusNew", [
            'idDataset' => $id
        ]);
    }

    #[Get(path: '/dataset/{id}/corpus/grid')]
    public function corpusGrid(int $id)
    {
        $corpus = Criteria::table("view_corpus as c")
            ->join("dataset_corpus", "c.idCorpus", "=", "dataset_corpus.idCorpus")
            ->where("dataset_corpus.idDataset", $id)
            ->where("c.idLanguage", AppService::getCurrentIdLanguage())
            ->all();
        return view("Dataset.corpusGrid", [
            'idDataset' => $id,
            'corpus' => $corpus
        ]);
    }

    #[Post(path: '/dataset/{id}/corpus/new')]
    public function corpusNew(CorpusData $data)
    {
        Criteria::table("dataset_corpus")->insert($data->toArray());
        $this->trigger('reload-gridDatasetCorpus');
        return $this->renderNotify("success", "Corpus associated with Dataset.");
    }

    #[Delete(path: '/dataset/{id}/corpus/{idCorpus}')]
    public function delete(int $id, int $idCorpus)
    {
        try {
            Criteria::table("dataset_corpus")
                ->where("idCorpus", $idCorpus)
                ->where("idDataset", $id)
                ->delete();
            $this->trigger('reload-gridDatasetCorpus');
            return $this->renderNotify("success", "Corpus removed from Dataset.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }


}
