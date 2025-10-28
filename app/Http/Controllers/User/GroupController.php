<?php

namespace App\Http\Controllers\User;

use App\Database\Criteria;
use App\Http\Controllers\Controller;
use Collective\Annotations\Routing\Attributes\Attributes\Delete;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Illuminate\Http\Request;

#[Middleware('master')]
class GroupController extends Controller
{
    #[Get(path: '/user/{id}/groups')]
    public function groups(int $id)
    {
        return view('User.groups', [
            'idUser' => $id,
        ]);
    }

    #[Get(path: '/user/{id}/groups/formNew')]
    public function groupsFormNew(int $id)
    {
        return view('User.groupsNew', [
            'idUser' => $id,
        ]);
    }

    #[Get(path: '/user/{id}/groups/grid')]
    public function groupsGrid(int $id)
    {
        $groups = Criteria::table('user_group')
            ->join('group', 'user_group.idGroup', '=', 'group.idGroup')
            ->select('group.*', 'user_group.idUser')
            ->where('user_group.idUser', $id)
            ->all();

        return view('User.groupsGrid', [
            'idUser' => $id,
            'groups' => $groups,
        ]);
    }

    #[Post(path: '/user/{id}/groups/new')]
    public function groupsNew(Request $request)
    {
        $idUser = $request->input('idUser');
        $idGroup = $request->input('idGroupAdd');

        try {
            Criteria::create('user_group', [
                'idUser' => $idUser,
                'idGroup' => $idGroup,
            ]);
            $this->trigger('reload-gridUserGroups');

            return $this->renderNotify('success', 'Group added to user.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }

    #[Delete(path: '/user/{idUser}/groups/{idGroup}')]
    public function groupsDelete(int $idUser, int $idGroup)
    {
        try {
            Criteria::table('user_group')
                ->where('idUser', $idUser)
                ->where('idGroup', $idGroup)
                ->delete();
            $this->trigger('reload-gridUserGroups');

            return $this->renderNotify('success', 'Group removed from user.');
        } catch (\Exception $e) {
            return $this->renderNotify('error', $e->getMessage());
        }
    }
}
