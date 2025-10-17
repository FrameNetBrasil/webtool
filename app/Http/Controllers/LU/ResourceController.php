<?php

namespace App\Http\Controllers\LU;

use App\Data\LU\CreateData;
use App\Data\LU\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\LU;
use App\Services\AppService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware(name: 'auth')]
class ResourceController extends Controller
{
    #[Post(path: '/lu')]
    public function newLU(CreateData $data)
    {
        try {
            $exists = Criteria::table('lu')
                ->where('idLemma', $data->idLemma)
                ->where('idFrame', $data->idFrame)
                ->first();
            if (! is_null($exists)) {
                throw new \Exception('LU already exists.');
            }
            Criteria::function('lu_create(?)', [$data->toJson()]);
            $this->trigger('reload-gridLU');

            return $this->renderNotify('success', 'LU created.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/lu/{id}/edit')]
    public function edit(string $id)
    {
        return view('LU.edit', [
            'lu' => LU::byId($id),
            'mode' => 'edit',
        ]);
    }

    #[Get(path: '/lu/{id}/object')]
    public function object(string $id)
    {
        return view('LU.object', [
            'lu' => LU::byId($id),
            'mode' => 'object',
        ]);
    }

    #[Delete(path: '/lu/{id}')]
    public function delete(string $id)
    {
        try {
            $lu = LU::byId($id);
            $a = Criteria::table('annotation')
                ->where('idEntity', $lu->idEntity)
                ->all();
            if (count($a) > 0) {
                throw new \Exception('LU has annotations.');
            }
            Criteria::function('lu_delete(?, ?)', [
                $id,
                AppService::getCurrentIdUser(),
            ]);
            $this->trigger('reload-gridLU');

            return $this->renderNotify('success', 'LU deleted.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/lu/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view('LU.formEdit', [
            'lu' => LU::byId($id),
        ]);
    }

    #[Put(path: '/lu/{id}')]
    public function update(UpdateData $data)
    {
        if ($data->idFrame == '') {
            $lu = LU::byId($data->idLU);
            $data->idFrame = $lu->idFrame;
        }
        LU::update($data);
        $this->trigger('reload-gridLU');

        return $this->renderNotify('success', 'LU updated.');
    }
}
