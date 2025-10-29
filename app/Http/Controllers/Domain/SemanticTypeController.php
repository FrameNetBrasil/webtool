<?php

namespace App\Http\Controllers\Domain;

use App\Data\SemanticType\CreateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware('master')]
class SemanticTypeController extends Controller
{
    #[Get(path: '/domain/{id}/semanticTypes')]
    public function semanticTypes(int $id)
    {
        return view('Domain.semanticTypes', [
            'idDomain' => $id,
        ]);
    }

    #[Get(path: '/domain/{id}/semanticTypes/formNew')]
    public function semanticTypesFormNew(int $id)
    {
        return view('Domain.semanticTypesNew', [
            'idDomain' => $id,
        ]);
    }

    #[Get(path: '/domain/{id}/semanticTypes/grid')]
    public function semanticTypesGrid(int $id)
    {
        $semanticTypes = Criteria::byFilterLanguage('view_semantictype', [])
            ->where('idDomain', $id)
            ->select('idSemanticType', 'name', 'description')
            ->orderBy('name')
            ->all();

        return view('Domain.semanticTypesGrid', [
            'idDomain' => $id,
            'semanticTypes' => $semanticTypes,
        ]);
    }

    #[Post(path: '/domain/semanticTypes/new')]
    public function semanticTypesNew(CreateData $data)
    {
        $idSemanticType = $data->idSemanticType;
        if ($idSemanticType == 0) {
            // Create new semantic type
            $json = json_encode([
                'idDomain' => $data->idDomain,
                'nameEn' => $data->name,
                'idUser' => $data->idUser,
            ]);
            $idSemanticType = Criteria::function('semantictype_create(?)', [$json]);
        } else {
            // Update existing semantic type's domain
            Criteria::table('semantictype')
                ->where('idSemanticType', $idSemanticType)
                ->update(['idDomain' => $data->idDomain]);
        }

        $this->trigger('reload-gridSemanticTypes');

        return $this->renderNotify('success', 'SemanticType added to domain.');
    }

    #[Delete(path: '/domain/{idDomain}/semanticTypes/{idSemanticType}')]
    public function semanticTypesDelete(int $idDomain, int $idSemanticType)
    {
        Criteria::table('semantictype')
            ->where('idSemanticType', $idSemanticType)
            ->update(['idDomain' => null]);

        $this->trigger('reload-gridSemanticTypes');

        return $this->renderNotify('success', 'SemanticType removed from domain.');
    }
}
