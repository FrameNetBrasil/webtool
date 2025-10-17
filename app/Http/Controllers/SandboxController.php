<?php

namespace App\Http\Controllers;

use App\Services\AnnotationDeixisService;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;

#[Middleware(name: 'auth')]
class SandboxController extends Controller
{
    #[Get(path: '/sandbox/page1')]
    public function page1()
    {
        $object = AnnotationDeixisService::getObject(12861);
        return view("Sandbox.page1", [
            'data' => [],
            'object' => $object
        ]);
    }

    #[Get(path: '/sandbox/page2')]
    public function page2()
    {
        $object = AnnotationDeixisService::getObject(12861);
        return view("Sandbox.page2", [
            'data' => [],
            'object' => $object
        ]);
    }
}
