<?php

namespace App\Services;

use App\Database\Criteria;
use App\Repositories\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class AppService
{
    static public function languagesDescription()
    {
        return Criteria::table('language')
            ->treeResult('idLanguage');
    }

    public static function getCurrentLanguageCode()
    {
        return session('currentLanguage')->language;
    }
    public static function getCurrentLanguage()
    {
        return session('currentLanguage');
    }

    public static function setCurrentLanguage(int $idLanguage)
    {
        $languages = self::languagesDescription();
        $data = $languages[$idLanguage][0];
        $data->idLanguage = $idLanguage;
        session(['currentLanguage' => $data]);
    }

    public static function getCurrentIdLanguage()
    {
        return session('currentLanguage')->idLanguage ?? session('idLanguage');
    }

    public static function availableLanguages()
    {
        $data = [];
        $languages = config('webtool.user')[3]['language'][3];
        foreach ($languages as $l => $language) {
            $data[] = (object)[
                'idLanguage' => $l,
                'description' => $language[0]
            ];
        }
        return $data;
    }

    public static function setLocale()
    {
        App::setLocale(AppService::getCurrentLanguage()->language);
    }

    static public function userLevel(): array
    {
        return Criteria::table("group")->chunkResult('idGroup', 'name');
    }

    public static function getCurrentUser(): ?object
    {
        return Auth::user();
    }

    public static function getCurrentIdUser(): ?int
    {
        $user = Auth::user();

        return $user ? $user->idUser : 0;
    }

    public static function checkAccess(string $group): bool
    {
        if ($group == '') {
            return true;
        }

        if (! Auth::check()) {
            return false;
        }

        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // Get full user with groups
        $userWithGroups = User::byId($user->idUser);

        return User::isMemberOf($userWithGroups, $group) || User::isManager($userWithGroups);
    }

}
