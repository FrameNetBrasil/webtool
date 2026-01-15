<?php

namespace App\Http\Controllers\Frame;

use App\Data\Frame\SearchData;
use App\Data\Frame\SearchNamespaceData;
use App\Http\Controllers\Controller;
use App\Services\Frame\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("web")]
class BrowseNamespaceController extends Controller
{
    #[Get(path: '/namespace')]
    public function browse(SearchNamespaceData $search)
    {
        $frames = BrowseService::browseNamespaceFrameBySearch($search);
        return view('Frame.Report.mainNamespace', [
            'data' => $frames,
        ]);
    }

    #[Post(path: '/namespace/search')]
    public function tree(SearchNamespaceData $search)
    {
        $data = BrowseService::browseNamespaceFrameBySearch($search);

        return view('Frame.Report.mainNamespace', [
            'data' => $data,
        ])->fragment('search');

    }

}
