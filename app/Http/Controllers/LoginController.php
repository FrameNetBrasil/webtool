<?php

namespace App\Http\Controllers;

use App\Data\ImperData;
use App\Data\LoginData;
use App\Data\TwoFactorData;
use App\Database\Criteria;
use App\Exceptions\LoginException;
use App\Exceptions\UserNewException;
use App\Exceptions\UserPendingException;
use App\Mail\WebToolMail;
use App\Models\User;
use App\Services\AuthUserService;
use Auth0\SDK\Auth0;
use Auth0\SDK\Exception\StateException;
use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Collective\Annotations\Routing\Attributes\Attributes\Middleware;
use Collective\Annotations\Routing\Attributes\Attributes\Post;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

#[Middleware(name: 'web')]
class LoginController extends Controller
{
    private function getAuth0()
    {
        $auth0 = new Auth0([
            'domain' => env('AUTH0_DOMAIN'),
            'clientId' =>  env('AUTH0_CLIENT_ID'),
            'clientSecret' => env('AUTH0_CLIENT_SECRET'),
            'cookieSecret' => env('AUTH0_COOKIE_SECRET'),
            'redirect_uri' => env('AUTH0_CALLBACK_URL'),
            'tokenAlgorithm' => 'HS256'
        ]);
        return $auth0;
    }


    #[Get(path: '/main/auth0Callback')]
    public function auth0Callback()
    {
        try {
            $redirectURI = env('AUTH0_CALLBACK_URL');
            $auth0 = $this->getAuth0();
            $auth0->exchange($redirectURI);

            $userInfo = $auth0->getUser();
            $user = new AuthUserService();
            $status = $user->auth0Login($userInfo);

            if ($status == 'new') {
                throw new UserNewException('User registered. Wait for Administrator approval.');
            } elseif ($status == 'pending') {
                throw new UserPendingException('User already registered, but waiting for Administrator approval.');
            } elseif ($status == 'logged') {
                return redirect("/");
            } else {
                throw new LoginException('Login failed; contact administrator.');
            }
        } catch (StateException $e) {
            throw new LoginException("Auth0: Invalid authorization code.");
        }
    }

    #[Get(path: '/auth0Login')]
    public function auth0Login()
    {
        $auth0 = $this->getAuth0();
        $auth0->clear();
        $redirectURI = env('AUTH0_CALLBACK_URL');
        //header("Location: " . $auth0->login($redirectURI));
        return redirect($auth0->login($redirectURI));
    }

    #[Post(path: '/login')]
    public function login(LoginData $data)
    {
        debug($data);
        $user = new AuthUserService();
        $status = $user->md5Check($data);

        if ($status == 'new') {
            throw new UserNewException('User registered. Wait for Administrator approval.');
        } elseif ($status == 'pending') {
            throw new UserPendingException('User already registered, but waiting for Administrator approval.');
        } elseif ($status == 'checked') {

            return $this->redirect("/twofactor");
        } else {
            throw new LoginException('Login failed; contact administrator.');
        }
    }

    #[Get(path: '/twofactor')]
    public function twofactor()
    {
        return view("App.twofactor");
    }

    #[Post(path: '/twofactor')]
    public function twofactorPost(TwoFactorData $data)
    {
        debug($data);
        $user = new AuthUserService();
        $user->md5TwoFactor($data);
        return $this->redirect("/");
    }
    #[Get(path: '/login-error')]
    public function loginError()
    {
        return view("App.login")->fragment('form');
    }

    #[Get(path: '/logout')]
    public function logout()
    {
        Auth::logout();
        session()->flush();
        if (config('webtool.login.handler') == 'auth0') {
            $auth0 = $this->getAuth0();
            $auth0->logout('/');
        }
        return redirect("/");
    }

    #[Get(path: '/impersonating')]
    public function impersonating()
    {
        $token = md5(uniqid(rand(), true));
        session(['mail_token' => $token]);
        Mail::to("ely.matos@gmail.com")->send(new WebToolMail($token));
        return view("App.impersonating", []);
    }

    #[Post(path: '/impersonating')]
    public function impersonatingPost(ImperData $data)
    {
        $token = session('mail_token');
        if ($token == $data->password) {
            $user = new AuthUserService();
            $user->impersonate($data->idUser);
            return $this->redirect("/");
        } else {
            return $this->notify('error', "Access denied.");
        }
    }



}
