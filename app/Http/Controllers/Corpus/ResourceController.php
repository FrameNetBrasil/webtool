<?php

namespace App\Http\Controllers\Corpus;

use App\Data\ComboBox\QData;
use App\Data\Corpus\CreateData;
use App\Data\Corpus\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Corpus;
use App\Services\Annotation\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class ResourceController extends Controller
{

    #[Get(path: '/corpus/{id}/edit')]
    public function edit(string $id)
    {
        return view("Corpus.edit",[
            'corpus' => Corpus::byId($id)
        ]);
    }

    #[Get(path: '/corpus/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view("Corpus.formEdit",[
            'corpus' => Corpus::byId($id)
        ]);
    }

    #[Post(path: '/corpus')]
    public function update(UpdateData $data)
    {
        try {
            Criteria::function('dataset_update(?)', [$data->toJson()]);
            $this->trigger("reload-gridDataset");
            return $this->renderNotify("success", "Dataset updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/corpus/new')]
    public function new()
    {
        return view("Corpus.formNew");
    }

    #[Post(path: '/corpus/new')]
    public function create(CreateData $data)
    {
        try {
            Criteria::function('corpus_create(?)', [$data->toJson()]);
            $this->trigger("reload-gridCorpus");
            return $this->renderNotify("success", "Corpus created.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/corpus/{id}')]
    public function delete(string $id)
    {
        try {
            Criteria::deleteById("corpus","idCorpus",$id);
            return $this->clientRedirect("/corpus");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Get(path: '/corpus/listForSelect')]
    public function listForSelect(QData $data)
    {
        $name = (strlen($data->q) > 2) ? $data->q : 'none';
        return ['results' => Criteria::byFilterLanguage("view_corpus",["name","startswith",$name])->orderby("name")->all()];
    }
}
