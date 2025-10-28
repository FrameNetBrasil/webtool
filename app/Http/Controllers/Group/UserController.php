<?php

namespace App\Http\Controllers\Group;

use App\Database\Criteria;
use App\Http\Controllers\Controller;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Illuminate\Http\Request;

#[Middleware('master')]
class UserController extends Controller
{
    #[Get(path: '/group/{id}/users')]
    public function users(int $id)
    {
        return view('Group.users', [
            'idGroup' => $id,
        ]);
    }

    #[Get(path: '/group/{id}/users/formNew')]
    public function usersFormNew(int $id)
    {
        return view('Group.usersNew', [
            'idGroup' => $id,
        ]);
    }

    #[Get(path: '/group/{id}/users/grid')]
    public function usersGrid(int $id)
    {
        $users = Criteria::table('user_group')
            ->join('user', 'user_group.idUser', '=', 'user.idUser')
            ->select('user.*', 'user_group.idGroup')
            ->where('user_group.idGroup', $id)
            ->all();

        return view('Group.usersGrid', [
            'idGroup' => $id,
            'users' => $users,
        ]);
    }

    #[Post(path: '/group/{id}/users/new')]
    public function usersNew(Request $request)
    {
        $idGroup = $request->input('idGroup');
        $idUser = $request->input('idUser');

        try {
            Criteria::create('user_group', [
                'idUser' => $idUser,
                'idGroup' => $idGroup,
            ]);
            $this->trigger('reload-gridGroupUsers');

            return $this->renderNotify('success', 'User added to group.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/group/{idGroup}/users/{idUser}')]
    public function usersDelete(int $idGroup, int $idUser)
    {
        try {
            Criteria::table('user_group')
                ->where('idUser', $idUser)
                ->where('idGroup', $idGroup)
                ->delete();
            $this->trigger('reload-gridGroupUsers');

            return $this->renderNotify('success', 'User removed from group.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}
