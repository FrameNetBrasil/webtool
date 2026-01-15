<?php

namespace App\Http\Controllers\Class;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FE\BrowseController as FEBrowseController;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware("master")]
class FEController extends Controller
{
    #[Get(path: '/class/{id}/fes')]
    public function fes(string $id)
    {
        return view("Class.fes",[
            'idFrame' => $id
        ]);
    }

    #[Get(path: '/class/{id}/fes/formNew')]
    public function formNewFE(string $id)
    {
        return view("FE.formNewClass",[
            'idFrame' => $id
        ]);
    }

    #[Get(path: '/class/{id}/fes/grid')]
    public function gridFE(string $id)
    {
        return view("FE.grid",[
            'idFrame' => $id,
            'fes' => FEBrowseController::listForGridByFrame($id)
        ]);
    }

}
