<?php

namespace App\Http\Controllers\Frame;

use App\Data\CreateFrameData;
use App\Data\CreateRelationFEInternalData;
use App\Data\Frame\UpdateClassificationData;
use App\Data\SearchFrameData;
use App\Data\UpdateFrameClassificationData;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FE\FEController;
use App\Repositories\Entry;
use App\Repositories\Frame;
use App\Services\AppService;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware("master")]
class ClassificationController extends Controller
{
    #[Get(path: '/frame/{id}/classification')]
    public function classification(string $id)
    {
        $frame = Frame::byId($id);
        return view("Classification.child",[
            'idFrame' => $id,
            'frame' => $frame
        ]);
    }

    #[Get(path: '/frame/{id}/classification/formFramalType')]
    public function formFramalType(string $id)
    {
        return view("Classification.formFramalType",[
            'idFrame' => $id
        ]);
    }

    #[Get(path: '/frame/{id}/classification/formFramalDomain')]
    public function formFramalDomain(string $id)
    {
        return view("Classification.formFramalDomain",[
            'idFrame' => $id
        ]);
    }

    #[Post(path: '/frame/classification/domain')]
    public function framalDomain(UpdateClassificationData $data)
    {
        try {
            RelationService::updateFramalDomain($data);
            return $this->renderNotify("success", "Domain updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

    #[Post(path: '/frame/classification/type')]
    public function framalType(UpdateClassificationData $data)
    {
        try {
            RelationService::updateFramalType($data);
            return $this->renderNotify("success", "Type updated.");
        } catch (\Exception $e) {
            return $this->renderNotify("error", $e->getMessage());
        }
    }

}
