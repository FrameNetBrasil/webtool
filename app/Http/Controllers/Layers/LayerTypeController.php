<?php

namespace App\Http\Controllers\Layers;

use App\Data\Layers\CreateLayerTypeData;
use App\Data\Layers\UpdateLayerTypeData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\LayerType;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware('master')]
class LayerTypeController extends Controller
{
    #[Get(path: '/layers/{id}/layertypes')]
    public function layerTypes(int $id)
    {
        return view('Layers.layerTypes', [
            'idLayerGroup' => $id,
        ]);
    }

    #[Get(path: '/layers/{id}/layertypes/formNew')]
    public function layerTypesFormNew(int $id)
    {
        return view('Layers.layerTypesFormNew', [
            'idLayerGroup' => $id,
        ]);
    }

    #[Get(path: '/layers/{id}/layertypes/grid')]
    public function layerTypesGrid(int $id)
    {
        $layerTypes = Criteria::table('view_layertype')
            ->where('idLayerGroup', $id)
            ->where('idLanguage', 2)
            ->orderBy('layerOrder')
            ->all();

        return view('Layers.layerTypesGrid', [
            'idLayerGroup' => $id,
            'layerTypes' => $layerTypes,
        ]);
    }

    #[Post(path: '/layers/{id}/layertypes/new')]
    public function layerTypesNew(CreateLayerTypeData $data)
    {
        try {
            $exists = Criteria::table('view_layertype')
                ->whereRaw("name = '{$data->nameEn}' collate 'utf8mb4_bin'")
                ->where('idLanguage', 2)
                ->where('idLayerGroup', $data->idLayerGroup)
                ->first();
            if (! is_null($exists)) {
                throw new \Exception('LayerType already exists in this LayerGroup.');
            }
            $json = json_encode((object) [
                'nameEn' => $data->nameEn,
                'allowsApositional' => $data->allowsApositional,
                'isAnnotation' => $data->isAnnotation,
                'layerOrder' => $data->layerOrder,
                'idLayerGroup' => $data->idLayerGroup,
            ]);
            Criteria::function('layertype_create(?)', [$json]);
            $this->trigger('reload-gridLayerTypes');

            return $this->renderNotify('success', 'LayerType added to LayerGroup.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/layers/{idLayerGroup}/layertypes/{idLayerType}')]
    public function layerTypesDelete(int $idLayerGroup, int $idLayerType)
    {
        try {
            Criteria::function('layertype_delete(?,?)', [$idLayerType, AppService::getCurrentIdUser()]);
            $this->trigger('reload-gridLayerTypes');

            return $this->renderNotify('success', 'LayerType removed from LayerGroup.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/layertype/{id}/edit')]
    public function edit(string $id)
    {
        return view('Layers.LayerType.edit', [
            'layerType' => LayerType::byId($id),
        ]);
    }

    #[Get(path: '/layertype/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view('Layers.LayerType.formEdit', [
            'layerType' => LayerType::byId($id),
        ]);
    }

    #[Put(path: '/layertype')]
    public function update(UpdateLayerTypeData $data)
    {
        try {
            Criteria::table('layertype')
                ->where('idLayerType', $data->idLayerType)
                ->update($data->toArray());

            return $this->renderNotify('success', 'LayerType updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}
