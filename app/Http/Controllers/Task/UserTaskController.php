<?php

namespace App\Http\Controllers\Task;

use App\Data\Dataset\CreateData;
use App\Data\Dataset\ProjectData;
use App\Data\Dataset\SearchData;
use App\Data\Dataset\UpdateData;
use App\Data\Task\UserTaskData;
use App\Data\Task\UserTaskDocumentData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Dataset;
use App\Repositories\Document;
use App\Repositories\UserTask;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware("master")]
class UserTaskController extends Controller
{
    #[Get(path: '/usertask/{id}/edit')]
    public function edit(string $id)
    {
        $usertask = UserTask::byId($id);
        if (!$usertask) {
            abort(404, 'UserTask not found');
        }
        return view("UserTask.edit",[
            'usertask' => $usertask
        ]);
    }

    #[Get(path: '/usertask/{id}/documents')]
    public function documents(string $id)
    {
        return view("UserTask.documents",[
            'idUserTask' => $id
        ]);
    }

    #[Get(path: '/usertask/{id}/documents/formNew')]
    public function documentsNew(string $id)
    {
        return view("UserTask.documentsNew",[
            'idUserTask' => $id
        ]);
    }

    #[Get(path: '/usertask/{id}/documents/grid')]
    public function documentsGrid(string $id)
    {
        $documents = Criteria::table("view_usertask_docs as utd")
            ->select("utd.idUserTaskDocument","utd.idDocument","utd.documentName","utd.idCorpus","utd.corpusName")
            ->where("utd.idUserTask", $id)
            ->where("utd.idLanguage","=", AppService::getCurrentIdLanguage())
            ->all();
        return view("UserTask.documentsGrid",[
            'idUserTask' => $id,
            'documents' => $documents
        ]);
    }

    #[Post(path: '/usertask/documents/new')]
    public function create(UserTaskDocumentData $data)
    {
        try {
            if (is_null($data->idCorpus)) {
                $document= Document::byId($data->idDocument);
                $data->idCorpus = $document->idCorpus;
            }
            Criteria::table("usertask_document")
                ->insert($data->toArray());
            $this->trigger("reload-gridUserTaskDocuments");
            return $this->renderNotify("success", "Document added to UserTask.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/usertask/{idUserTask}')]
    public function deleteUserTask(string $idUserTask)
    {
        try {
            Criteria::table("usertask_document")
                ->where("idUserTask", $idUserTask)
                ->delete();
            Criteria::table("usertask")
                ->where("idUserTask", $idUserTask)
                ->delete();
            $this->trigger("reload-gridUserTaskDocuments");
            return $this->renderNotify("success", "Document removed from UserTask.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Delete(path: '/usertask/{idUserTask}/documents/{idUserTaskDocument}')]
    public function deleteUserTaskDocument(string $idUserTask, string $idUserTaskDocument)
    {
        try {
            Criteria::table("usertask_document")
                ->where("idUserTaskDocument", $idUserTaskDocument)
                ->delete();
            $this->trigger("reload-gridUserTaskDocuments");
            return $this->renderNotify("success", "Document removed from UserTask.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}
