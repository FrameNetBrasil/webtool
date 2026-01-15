<?php

namespace App\Http\Controllers\Docs;

use App\Http\Controllers\Controller;
use App\Services\DocsService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware('web')]
class DocsController extends Controller
{
    #[Get(path: '/docs/{path?}', where: ['path' => '.*'])]
    public function browser(?string $path = null)
    {
        // Get tree structure
        $tree = DocsService::buildTree();

        // Get document content
        if ($path !== null) {
            $path = urldecode($path);
            $document = DocsService::getDocument($path);
            $previousPage = DocsService::getPreviousPage($path);
            $nextPage = DocsService::getNextPage($path);
        } else {
            // Show menu - no document
            $document = [
                'found' => false,
                'content' => null,
                'html' => null,
                'title' => 'Documentation',
                'toc' => [],
                'breadcrumbs' => [['text' => 'Documentation', 'path' => null]],
            ];
            $previousPage = null;
            $nextPage = null;
        }

        return view('Docs.browser', [
            'tree' => $tree,
            'document' => $document,
            'currentPath' => $path,
            'previousPage' => $previousPage,
            'nextPage' => $nextPage,
        ]);
    }

    #[Get(path: '/docs/content/{path}', where: ['path' => '.*'])]
    public function content(string $path)
    {
        $path = urldecode($path);
        $document = DocsService::getDocument($path);

        if (! $document['found']) {
            return response()->view('Docs.content', [
                'document' => [
                    'found' => false,
                    'html' => '<p class="ui message warning">Document not found.</p>',
                    'title' => 'Not Found',
                    'toc' => [],
                    'breadcrumbs' => [],
                ],
                'previousPage' => null,
                'nextPage' => null,
            ], 404);
        }

        $previousPage = DocsService::getPreviousPage($path);
        $nextPage = DocsService::getNextPage($path);

        return view('Docs.content', [
            'document' => $document,
            'previousPage' => $previousPage,
            'nextPage' => $nextPage,
        ]);
    }
}
