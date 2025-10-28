<?php

namespace App\Http\Controllers\Corpus;

use App\Data\Corpus\SearchData;
use App\Http\Controllers\Controller;
use App\Services\Corpus\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware('auth')]
class BrowseController extends Controller
{
    #[Get(path: '/corpus')]
    public function browse(SearchData $search)
    {
        $data = BrowseService::browseCorpusDocumentBySearch($search);

        return view('Corpus.browse', [
            'data' => $data,
            'search' => $search,
        ]);
    }

    #[Post(path: '/corpus/browse/search')]
    public function search(SearchData $search)
    {
        $title = '';
        $data = BrowseService::browseCorpusDocumentBySearch($search);

        // Handle tree expansion - when expanding a corpus, show documents without title
        if ($search->type === 'corpus' && $search->id != 0) {
            $title = ''; // No title for expansions
        }
        // Handle search filtering
        elseif (! empty($search->corpus)) {
            $title = 'Corpus';
        } elseif (! empty($search->document)) {
            $title = 'Documents';
        } else {
            $title = 'Corpus';
        }

        return view('Corpus.tree', [
            'data' => $data,
            'title' => $title,
        ]);
    }
}
