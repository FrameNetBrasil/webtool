<?php

namespace App\Http\Controllers\Frame;

use App\Data\Frame\UpdateClassificationData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Frame;
use App\Services\RelationService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware('master')]
class ClassificationController extends Controller
{
    #[Get(path: '/frame/{id}/classification')]
    public function classification(string $id)
    {
        $frame = Frame::byId($id);

        return view('Classification.child', [
            'idFrame' => $id,
            'frame' => $frame,
        ]);
    }

    #[Get(path: '/frame/{id}/classification/formFramalType')]
    public function formFramalType(string $id)
    {
        return view('Classification.formFramalType', [
            'idFrame' => $id,
        ]);
    }

    #[Get(path: '/frame/{id}/classification/formFramalDomain')]
    public function formFramalDomain(string $id)
    {
        return view('Classification.formFramalDomain', [
            'idFrame' => $id,
        ]);
    }

    #[Get(path: '/frame/{id}/classification/formNamespace')]
    public function formNamespace(string $id)
    {
        return view('Classification.formNamespace', [
            'idFrame' => $id,
        ]);
    }

    #[Post(path: '/frame/classification/domain')]
    public function framalDomain(UpdateClassificationData $data)
    {
        try {
            RelationService::updateFramalDomain($data);

            return $this->renderNotify('success', 'Domain updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/frame/classification/type')]
    public function framalType(UpdateClassificationData $data)
    {
        try {
            RelationService::updateFramalType($data);

            return $this->renderNotify('success', 'Type updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/frame/classification/namespace')]
    public function framalNamespace(UpdateClassificationData $data)
    {
        try {
            debug($data);
            Criteria::table('frame')
                ->where('idFrame', $data->idFrame)
                ->update([
                    'idNamespace' => $data->idNamespace,
                ]);

            return $this->renderNotify('success', 'Namespace updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}
