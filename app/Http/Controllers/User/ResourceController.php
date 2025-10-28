<?php

namespace App\Http\Controllers\User;

use App\Data\ComboBox\QData;
use App\Data\User\CreateData;
use App\Data\User\SearchData;
use App\Data\User\UpdateData;
use App\Database\Criteria;
use App\Http\Controllers\Controller;
use App\Repositories\Group;
use App\Repositories\User;
use App\Services\User\BrowseService;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Collective\Annotations\Routing\Attributes\Attributes\Put;

#[Middleware('master')]
class ResourceController extends Controller
{
    #[Get(path: '/user')]
    public function resource(SearchData $search)
    {
        $data = BrowseService::browseGroupUserBySearch($search);

        return view('User.browser', [
            'data' => $data,
            'search' => $search,
        ]);
    }

    #[Post(path: '/user/search')]
    public function search(SearchData $search)
    {
        $title = '';
        $data = BrowseService::browseGroupUserBySearch($search);

        // Handle tree expansion - when expanding a group, show users without title
        if ($search->type === 'group' && $search->id != 0) {
            $title = ''; // No title for expansions
        }
        // Handle search filtering
        elseif (! empty($search->group)) {
            $title = 'Groups';
        } elseif (! empty($search->user)) {
            $title = 'Users';
        } else {
            $title = 'Groups';
        }

        return view('User.tree', [
            'data' => $data,
            'title' => $title,
        ]);
    }

    #[Get(path: '/user/data')]
    public function data(SearchData $search)
    {
        if ($search->id != 0) {
            // Load users for a specific group
            $data = Criteria::table('user_group')
                ->join('user', 'user_group.idUser', '=', 'user.idUser')
                ->where('user_group.idGroup', $search->id)
                ->select('user.idUser', 'user.login as name', 'user.email')
                ->selectRaw("concat('u',user.idUser) as id")
                ->selectRaw("'open' as state")
                ->selectRaw("'user' as type")
                ->orderBy('user.login')->all();
        } else {
            if ($search->user == '') {
                // Load groups only
                $data = Criteria::table('group')
                    ->select('idGroup as id', 'idGroup', 'name')
                    ->selectRaw("'closed' as state")
                    ->selectRaw("'group' as type")
                    ->where('name', 'startswith', $search->group)
                    ->orderBy('name')
                    ->all();
            } else {
                // Search users
                $data = Criteria::table('user')
                    ->select('idUser', 'login as name', 'email')
                    ->selectRaw("concat('u',idUser) as id")
                    ->selectRaw("'open' as state")
                    ->selectRaw("'user' as type")
                    ->where(function ($query) use ($search) {
                        $query->where('login', 'startswith', $search->user)
                            ->orWhere('email', 'startswith', $search->user)
                            ->orWhere('name', 'startswith', $search->user);
                    })
                    ->orderBy('login')->all();
            }
        }

        return $data;
    }

    #[Get(path: '/user/new')]
    public function new()
    {
        return view('User.formNew');
    }

    #[Get(path: '/user/{id}/edit')]
    public function edit(string $id)
    {
        debug($id);

        return view('User.edit', [
            'user' => User::byId($id),
        ]);
    }

    #[Get(path: '/user/{id}/formEdit')]
    public function formEdit(string $id)
    {
        return view('User.formEdit', [
            'user' => User::byId($id),
        ]);
    }

    #[Put(path: '/user/{id}/authorize')]
    public function authorizeUser(string $id)
    {
        try {
            User::authorize($id);
            $this->trigger('reload-gridUser');

            return $this->renderNotify('success', 'User authorized.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Put(path: '/user/{id}/deauthorize')]
    public function deauthorizeUser(string $id)
    {
        try {
            User::deauthorize($id);

            //            $this->trigger("reload-gridUser");
            return $this->renderNotify('success', 'User deauthorized.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/user')]
    public function update(UpdateData $data)
    {
        try {
            // User::update($data);
            Criteria::function('user_update(?)', [$data->toJson()]);
            $this->trigger('reload-gridUser');

            return $this->renderNotify('success', 'User updated.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Post(path: '/user/new')]
    public function create(CreateData $user)
    {
        try {
            $user->groups = [Group::byId($user->idGroup)];
            $user->passMD5 = md5(config('webtool.defaultPassword'));
            User::create($user);
            $this->trigger('reload-gridUser');

            return $this->renderNotify('success', 'User created.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/user/{id}')]
    public function delete(string $id)
    {
        try {
            // User::delete($id);
            Criteria::function('user_delete(?)', [$id]);

            return $this->clientRedirect('/user');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Get(path: '/user/listForSelect')]
    public function listForSelect(QData $data)
    {
        $name = (strlen($data->q) > 1) ? $data->q : 'none';

        return ['results' => Criteria::byFilter('user',
            [['name', 'startswith', $name], ['status', '=', 1]])
            ->selectRaw("idUser,concat('#',idUser, ' ', name,' [',email,']') as name")
            ->orderby('name')
            ->all()];
    }
}
