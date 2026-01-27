<?php

namespace App\Http\Controllers\Image;

use App\Data\Image\DocumentData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware('auth')]
class DocumentController extends Controller
{
    #[Get(path: '/image/{id}/documents')]
    public function documents(int $id)
    {
        return view('Image.documents', [
            'idImage' => $id,
        ]);
    }

    #[Get(path: '/image/{id}/documents/formNew')]
    public function documentsFormNew(int $id)
    {
        return view('Image.documentsNew', [
            'idImage' => $id,
        ]);
    }

    #[Get(path: '/image/{id}/documents/grid')]
    public function documentsGrid(int $id)
    {
        $documents = Criteria::table('view_document_image as di')
            ->join("view_document as d", "di.idDocument", "=", "d.idDocument")
            ->select('d.idDocument', 'd.name', 'd.corpusName')
            ->where('di.idImage', $id)
            ->where('d.idLanguage', AppService::getCurrentIdLanguage())
            ->orderBy('d.name')
            ->all();

        return view('Image.documentsGrid', [
            'idImage' => $id,
            'documents' => $documents,
        ]);
    }

    #[Post(path: '/image/documents/new')]
    public function documentsNew(DocumentData $data)
    {
        try {
            if ($data->idDocument > 0) {
                Criteria::create('document_image',[
                    'idDocument' => $data->idDocument,
                    'idImage' => $data->idImage,
                ]);
            }
            $this->trigger('reload-gridDocuments');
            return $this->renderNotify('success', 'Document added to corpus.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/image/{idCorpus}/documents/{idDocument}')]
    public function documentsDelete(int $idImage, int $idDocument)
    {
        try {
            Criteria::table('document_image')
            ->where('idImage', $idImage)
            ->where('idDocument', $idDocument)
            ->delete();

            $this->trigger('reload-gridDocuments');

            return $this->renderNotify('success', 'Document removed from image.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}
