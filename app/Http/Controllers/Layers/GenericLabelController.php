<?php

namespace App\Http\Controllers\Layers;

use App\Data\Layers\CreateGenericLabelData;
use App\Data\Layers\UpdateGenericLabelData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\GenericLabel;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware('master')]
class GenericLabelController extends Controller
{
    #[Get(path: '/layertype/{id}/genericlabels')]
    public function genericLabels(int $id)
    {
        return view('Layers.GenericLabel.genericLabels', [
            'idLayerType' => $id,
        ]);
    }

    #[Get(path: '/layertype/{id}/genericlabels/formNew')]
    public function genericLabelsFormNew(int $id)
    {
        return view('Layers.GenericLabel.genericLabelsFormNew', [
            'idLayerType' => $id,
        ]);
    }

    #[Get(path: '/layertype/{id}/genericlabels/grid')]
    public function genericLabelsGrid(int $id)
    {
        $genericLabels = Criteria::table('genericlabel')
            ->where('idLayerType', $id)
            ->where('idLanguage', 2)
            ->orderBy('name')
            ->all();

        return view('Layers.GenericLabel.genericLabelsGrid', [
            'idLayerType' => $id,
            'genericLabels' => $genericLabels,
        ]);
    }

    #[Post(path: '/layertype/{id}/genericlabels/new')]
    public function genericLabelsNew(CreateGenericLabelData $data)
    {
        try {
            $exists = Criteria::table('genericlabel')
                ->whereRaw("name = '{$data->name}' collate 'utf8mb4_bin'")
                ->where('idLanguage', $data->idLanguage)
                ->where('idLayerType', $data->idLayerType)
                ->first();
            if (! is_null($exists)) {
                throw new \Exception('GenericLabel already exists in this LayerType.');
            }
            $json = json_encode((object) [
                'name' => $data->name,
                'idColor' => $data->idColor,
                'idLanguage' => $data->idLanguage,
                'definition' => $data->definition,
                'example' => '',
                'idLayerType' => $data->idLayerType,
                'idUser' => $data->idUser,
            ]);
            Criteria::function('genericlabel_create(?)', [$json]);
            $this->trigger('reload-gridGenericLabels');

            return $this->renderNotify('success', 'GenericLabel added to LayerType.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/layertype/{idLayerType}/genericlabels/{idGenericLabel}')]
    public function genericLabelsDelete(int $idLayerType, int $idGenericLabel)
    {
        try {
            Criteria::function('genericlabel_delete(?,?)', [$idGenericLabel, AppService::getCurrentIdUser()]);
            $this->trigger('reload-gridGenericLabels');

            return $this->renderNotify('success', 'GenericLabel removed from LayerType.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/genericlabel/{id}/edit')]
    public function edit(string $id)
    {
        return view('Layers.GenericLabel.edit', [
            'genericLabel' => GenericLabel::byId($id),
        ]);
    }

    #[Get(path: '/genericlabel/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view('Layers.GenericLabel.formEdit', [
            'genericLabel' => GenericLabel::byId($id),
        ]);
    }

    #[Put(path: '/genericlabel')]
    public function update(UpdateGenericLabelData $data)
    {
        try {
            Criteria::table('genericlabel')
                ->where('idGenericLabel', $data->idGenericLabel)
                ->update($data->toArray());

            return $this->renderNotify('success', 'GenericLabel updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}
