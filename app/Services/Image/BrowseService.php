<?php

namespace App\Services\Image;

use App\Database\Criteria;
use App\Repositories\AnnotationSet;
use App\Repositories\Project;
use App\Services\AppService;

class BrowseService
{
    public static function browseCorpusBySearch(object $search, array $projects = [], string $taskGroupName = '', bool $leaf = false): array
    {
        $corpusIcon = view('components.icon.corpus')->render();
        $data = [];
        debug("browseCorpusBySearch", $taskGroupName);
        $allowed = Project::getAllowedDocsForUser($projects, $taskGroupName);
        $allowedCorpus = array_keys(collect($allowed)->groupBy('idCorpus')->toArray());
        $corpus = Criteria::byFilterLanguage('view_corpus', ['name', 'startswith', $search->corpus])
            ->whereIn('idCorpus', $allowedCorpus)
            ->orderBy('name')->all();
        foreach ($corpus as $c) {
            $data[] = [
                'id' => $c->idCorpus,
                'text' => $corpusIcon . $c->name,
                'type' => 'corpus',
                'leaf' => $leaf,
            ];
        }

        return $data;
    }

    public static function browseDocumentsByCorpus(int $idCorpus, array $projects = [], string $taskGroupName = '', bool $leaf = false): array
    {
        $allowed = Project::getAllowedDocsForUser($projects, $taskGroupName, $idCorpus);
        $allowedDocuments = collect($allowed)->pluck('idDocument')->all();
        $documents = Criteria::table('view_document')
            ->select('idDocument', 'name as document', 'corpusName')
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->whereIn('idDocument', $allowedDocuments)
            ->orderBy('corpusName')->orderBy('name')->all();
        $data = array_map(fn($item) => [
            'id' => $item->idDocument,
            'text' => view('Annotation.partials.document', (array)$item)->render(),
            'type' => 'document',
            'leaf' => $leaf,
        ], $documents);
        return $data;
    }

    public static function browseImagesByDocument(int $idDocument): array
    {
        return self::browseImages(self::listImages($idDocument));
    }

    public static function browseImage(int $idImage): array
    {
        return self::browseImages(self::getImage($idImage));
    }

    public static function browseImages(array $images): array
    {
        $data = [];
        foreach ($images as $image) {
            $data[] = [
                'id' => $image->idImage,
                'formatedId' => '[#' . $image->idImage . ']',
                'extra' => '',
                'text' => $image->name,
                'type' => 'image',
                'leaf' => true,
            ];
        }

        return $data;
    }

    public static function getImage(int $idImage): array
    {
        $image = Criteria::table('image')
            ->where('idImage', $idImage)
            ->first();
        return [$image];
    }

    public static function listImages(int $idDocument): array
    {
        $images = Criteria::table('view_document_image as di')
            ->join('image as i', 'di.idImage', '=', 'i.idImage')
            ->join('document as d', 'di.idDocument', '=', 'd.idDocument')
            ->where('d.idDocument', $idDocument)
            ->select('i.idImage', 'i.name')
            ->orderBy('i.name')
            ->limit(1000)
            ->get()->keyBy('idImage')->all();
        return $images;
    }

}
