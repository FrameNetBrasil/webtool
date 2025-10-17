<?php

namespace App\Http\Controllers\Utils;

use App\Data\Corpus\SearchData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("auth")]
class BrowseController extends Controller
{
    #[Get(path: '/utils/importFullText')]
    public function browse(SearchData $search)
    {
        $data = $this->browseCorpusBySearch($search);

        return view('Utils.ImportFullText.browse', [
            'data' => $data,
        ]);
    }

    #[Post(path: '/utils/importFullText/search')]
    public function search(SearchData $search)
    {
        if (!is_null($search->idCorpus)) {
            $data = $this->browseDocumentsByCorpus($search->idCorpus);
        } else if ($search->document != '') {
            $data = $this->browseDocumentBySearch($search);
        } else {
            $data = $this->browseCorpusBySearch($search);
        }

        return view('Utils.ImportFullText.tree', [
            'data' => $data,
        ]);
    }

    private function browseCorpusBySearch(object $search): array
    {
        $corpusIcon = view('components.icon.corpus')->render();
        $data = [];
        $corpus = Criteria::byFilterLanguage('view_corpus', ['name', 'startswith', $search->corpus])
            ->orderBy('name')->all();
        foreach ($corpus as $c) {
            $data[] = [
                'id' => $c->idCorpus,
                'text' => $corpusIcon . $c->name,
                'type' => 'corpus',
                'leaf' => false,
            ];
        }

        return $data;
    }

    private function browseDocumentsByCorpus(int $idCorpus): array
    {
        $documents = Criteria::table('view_document')
            ->select('idDocument', 'name as document', 'corpusName')
            ->where('idCorpus', $idCorpus)
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->orderBy('corpusName')->orderBy('name')->all();
        $data = array_map(fn($item) => [
            'id' => $item->idDocument,
            'text' => view('Annotation.partials.document', (array)$item)->render(),
            'type' => 'document',
            'leaf' => true,
        ], $documents);
        return $data;
    }

    private function browseDocumentBySearch(SearchData $search): array
    {
        $documentIcon = view('components.icon.document')->render();
        if ($search->document != '') {
            $data = [];
            if (strlen($search->document) > 2) {
                $criteria = Criteria::byFilterLanguage('view_document', ['name', 'contains', $search->document])
                    ->select('idDocument', 'name', 'corpusName', 'idCorpus')
                    ->orderBy('corpusName')->orderBy('name');
                if ($search->corpus != '') {
                    $criteria = $criteria->where("corpusName", "startswith", $search->corpus);
                }
                $documents = $criteria->all();
                foreach ($documents as $document) {
                    $data[] = [
                        'id' => $document->idDocument,
                        'text' => $documentIcon . $document->corpusName . ' / ' . $document->name,
                        'type' => 'document',
                        'leaf' => true,
                    ];
                }
            }
        }
        return $data;
    }

}

