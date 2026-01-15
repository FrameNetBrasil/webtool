<?php

namespace App\Http\Controllers\Layers;

use App\Data\ComboBox\QData;
use App\Data\Layers\CreateLayerGroupData;
use App\Data\Layers\SearchData;
use App\Data\Layers\UpdateLayerGroupData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\LayerGroup;
use App\Services\Layers\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware('master')]
class LayerGroupController extends Controller
{
    #[Get(path: '/layers')]
    public function resource(SearchData $search)
    {
        $data = BrowseService::browseLayersBySearch($search);

        return view('Layers.browser', [
            'data' => $data,
            'search' => $search,
        ]);
    }

    #[Post(path: '/layers/search')]
    public function search(SearchData $search)
    {
        $data = BrowseService::browseLayersBySearch($search);

        return view('Layers.tree', [
            'data' => $data,
            'title' => '',
        ]);
    }

    #[Get(path: '/layers/grid')]
    public function grid(SearchData $search)
    {
        $data = BrowseService::browseLayersBySearch($search);

        return view('Layers.gridTree', [
            'data' => $data,
            'search' => $search,
        ]);
    }

    #[Get(path: '/layers/listForSelect')]
    public function listForSelect(QData $data)
    {
        return ['results' => LayerGroup::listForSelect($data->q)];
    }

    #[Get(path: '/layers/new')]
    public function new()
    {
        return view('Layers.formNew');
    }

    #[Get(path: '/layers/{id}/edit')]
    public function edit(string $id)
    {
        return view('Layers.edit', [
            'layerGroup' => LayerGroup::byId($id),
        ]);
    }

    #[Get(path: '/layers/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view('Layers.formEdit', [
            'layerGroup' => LayerGroup::byId($id),
        ]);
    }

    #[Post(path: '/layers')]
    public function update(UpdateLayerGroupData $data)
    {
        try {
            Criteria::table('layergroup')
                ->where('idLayerGroup', $data->idLayerGroup)
                ->update([
                    'name' => $data->name,
                    'type' => $data->type,
                ]);
            $this->trigger('reload-gridLayers');

            return $this->renderNotify('success', 'Layer Group updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/layers/new')]
    public function create(CreateLayerGroupData $data)
    {
        try {
            $exists = Criteria::table('layergroup')
                ->whereRaw("name = '{$data->name}' collate 'utf8mb4_bin'")
                ->first();
            if (! is_null($exists)) {
                throw new \Exception('Layer Group already exists.');
            }
            $newLayerGroup = [
                'name' => $data->name,
                'type' => $data->type,
            ];
            $idLayerGroup = Criteria::create('layergroup', $newLayerGroup);
            $this->trigger('reload-gridLayers');

            return $this->clientRedirect("/layers/{$idLayerGroup}/edit");
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/layers/{id}')]
    public function delete(int $id)
    {
        try {
            Criteria::deleteById('layergroup', 'idLayerGroup', $id);
            $this->trigger('clear-editarea', ['target' => '#editarea']);
            $this->trigger('reload-gridLayers');

            return $this->clientRedirect('/layers');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}
