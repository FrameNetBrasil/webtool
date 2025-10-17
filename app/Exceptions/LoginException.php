<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LoginException extends Exception
{
    public function report(): void
    {
    }

    public function render(Request $request): Response
    {
        $trigger = json_encode([
            'notify' => [
                'type' => 'error',
                'message' => $this->getMessage()
            ],
        ]);
        return response('', 204)->header('HX-Trigger', $trigger);

    }

}
