<?php

namespace App\Http\Controllers\Construction;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FE\BrowseController as FEBrowseController;
use App\Repositories\ConstructionElement;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class CEController extends Controller
{
    #[Get(path: '/cxn/{id}/ces')]
    public function ces(string $id)
    {
        return view("Construction.ces",[
            'idConstruction' => $id
        ]);
    }

    #[Get(path: '/cxn/{id}/ces/formNew')]
    public function formNewCE(string $id)
    {
        return view("CE.formNew",[
            'idConstruction' => $id
        ]);
    }

    #[Get(path: '/cxn/{id}/ces/grid')]
    public function gridCE(string $id)
    {
        return view("CE.grid",[
            'idConstruction' => $id,
            'ces' => ConstructionElement::listForGridByCxn($id)
        ]);
    }

}
