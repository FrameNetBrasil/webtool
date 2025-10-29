<?php

namespace App\Http\Controllers\Domain;

use App\Data\Domain\CreateData;
use App\Data\Domain\SearchData;
use App\Data\Domain\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Domain;
use App\Services\Domain\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;

#[Middleware('master')]
class ResourceController extends Controller
{
    #[Get(path: '/domain')]
    public function resource(SearchData $search)
    {
        $data = BrowseService::browseDomainSemanticTypeBySearch($search);

        return view('Domain.browser', [
            'title' => 'Domain/SemanticType',
            'data' => $data,
        ]);
    }

    #[Post(path: '/domain/search')]
    public function search(SearchData $search)
    {
        $title = '';
        $data = BrowseService::browseDomainSemanticTypeBySearch($search);

        // Handle tree expansion - when expanding nodes, show children without title
        if ($search->type === 'domain' && $search->id != 0) {
            $title = ''; // No title when expanding domain
        } elseif ($search->type === 'semanticType' && $search->id != 0) {
            $title = ''; // No title when expanding semantic type
        }
        // Handle search filtering
        elseif (! empty($search->domain)) {
            $title = 'Domains';
        } elseif (! empty($search->semanticType)) {
            $title = 'SemanticTypes';
        } else {
            $title = 'Domains';
        }

        return view('Domain.tree', [
            'data' => $data,
            'title' => $title,
        ]);
    }

    #[Get(path: '/domain/new')]
    public function new()
    {
        return view('Domain.formNew');
    }

    #[Get(path: '/domain/grid/{fragment?}')]
    #[Post(path: '/domain/grid/{fragment?}')]
    public function grid(SearchData $search, ?string $fragment = null)
    {
        debug($search);
        $domains = Domain::listToGrid($search);
        // debug($users);
        $view = view('Domain.grid', [
            'domains' => $domains,
        ]);

        return is_null($fragment) ? $view : $view->fragment('search');
    }

    #[Get(path: '/domain/{id}/edit')]
    public function edit(string $id)
    {
        return view('Domain.edit', [
            'domain' => Domain::byId($id),
        ]);
    }

    #[Get(path: '/domain/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view('Domain.formEdit', [
            'domain' => Domain::byId($id),
        ]);
    }

    #[Post(path: '/domain')]
    public function update(UpdateData $data)
    {
        try {
            Domain::update($data);

            return $this->renderNotify('success', 'Domain updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/domain/new')]
    public function create(CreateData $data)
    {
        try {
            $idDomain = Criteria::function('domain_create(?)', [$data->toJson()]);
            $this->trigger('reload-gridSemanticType');

            return $this->renderNotify('success', 'Domain created.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/domain/{id}')]
    public function delete(string $id)
    {
        try {
            Criteria::deleteById('domain', 'idDomain', $id);

            return $this->clientRedirect('/domain');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}
