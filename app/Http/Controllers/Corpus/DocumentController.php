<?php

namespace App\Http\Controllers\Corpus;

use App\Data\Document\CreateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Illuminate\Http\Request;

#[Middleware('auth')]
class DocumentController extends Controller
{
    #[Get(path: '/corpus/{id}/documents')]
    public function documents(int $id)
    {
        return view('Corpus.documents', [
            'idCorpus' => $id,
        ]);
    }

    #[Get(path: '/corpus/{id}/documents/formNew')]
    public function documentsFormNew(int $id)
    {
        return view('Corpus.documentsNew', [
            'idCorpus' => $id,
        ]);
    }

    #[Get(path: '/corpus/{id}/documents/grid')]
    public function documentsGrid(int $id)
    {
        $documents = Criteria::table('view_document')
            ->select('idDocument', 'name', 'corpusName')
            ->where('idCorpus', $id)
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->orderBy('name')
            ->all();

        return view('Corpus.documentsGrid', [
            'idCorpus' => $id,
            'documents' => $documents,
        ]);
    }

    #[Post(path: '/corpus/documents/new')]
    public function documentsNew(CreateData $data)
    {
        debug($data);
        try {
            // If no existing document selected, create a new one
            if ($data->name != '') {
                $idDocument= Criteria::function('document_create(?)', [$data->toJson()]);
            } elseif ($data->idDocument > 0) {
                // Update existing document to assign it to this corpus
                Criteria::table('document')
                    ->where('idDocument', $data->idDocument)
                    ->update(['idCorpus' => $data->idCorpus]);
            }

            $this->trigger('reload-gridDocuments');

            return $this->renderNotify('success', 'Document added to corpus.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/corpus/{idCorpus}/documents/{idDocument}')]
    public function documentsDelete(int $idCorpus, int $idDocument)
    {
        try {
            // Remove document from corpus (set idCorpus to null)
            Criteria::table('document')
                ->where('idDocument', $idDocument)
                ->update(['idCorpus' => null]);

            $this->trigger('reload-gridDocuments');

            return $this->renderNotify('success', 'Document removed from corpus.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}
