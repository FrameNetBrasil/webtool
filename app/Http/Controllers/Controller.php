<?php

namespace App\Http\Controllers;

use Collective\Annotations\Routing\Attributes\Attributes\Get;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected object $data;
    protected array $hx_trigger;

    public function __construct(
        protected readonly Request $request
    )
    {
        $this->hx_trigger = [];
    }

    public function isHtmx(): bool {
        return $this->request->header('HX-Request') === 'true';
    }

    #[Get(path: '/empty')]
    public function empty()
    {
        $response = response('', 200);
        return $response;
    }

    public function render(string $viewName, array $data = [], ?string $fragment = null)
    {
        $response = response()
            ->view($viewName, $data);
        if (!is_null($fragment)) {
            $response->fragment($fragment);
        }
        if (!empty($this->hx_trigger)) {
            $trigger = json_encode($this->hx_trigger);
            $response->header('HX-Trigger', $trigger);
        }
        return $response;
    }

    public function clientRedirect(string $url)
    {
        $response = response();
        return response('')
            ->withHeaders([
                'HX-Redirect' => $url
            ]);
    }

    public function redirect(string $url)
    {
        return response('')
            ->withHeaders([
                'HX-Redirect' => $url
            ]);
    }

    public function notify($type, $message)
    {
        $this->hx_trigger['notify'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    public function trigger(string $trigger, array $params = [])
    {
        $this->hx_trigger[$trigger] = $params;
    }

    public function renderNotify($type, $message)
    {
        $this->notify($type, $message);
        $trigger = json_encode($this->hx_trigger);
        $response = response('', 204)->header('HX-Trigger', $trigger);
        return $response;
    }

    public function renderTrigger(string $trigger, array $params = [])
    {
        $this->trigger($trigger,$params);
        $trigger = json_encode($this->hx_trigger);
        $response = response('', 204)->header('HX-Trigger', $trigger);
        return $response;
    }

}
