<?php

namespace App\Data;

use App\Http\Controllers\Index\MainController;
use Spatie\LaravelData\Data;

class LoginData extends Data
{
    public function __construct(
        public string $login,
        public string $password
    )
    {
    }

    public static function redirect(): string
    {
        return "/login-error";
    }
    public static function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }
}
