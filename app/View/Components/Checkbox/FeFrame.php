<?php

namespace App\View\Components\Checkbox;

use App\Database\Criteria;
use App\Repositories\Frame;
use App\Services\AppService;
use App\Services\FrameService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FeFrame extends Component
{

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id = '',
        public string $label = '',
        public int    $idFrame = 0,
        public array  $options = [],
        public ?array  $value = [],
    )
    {
        $icon = config('webtool.fe.icon');
        $fes = Criteria::table("view_frameelement")
            ->where("idLanguage", "=", AppService::getCurrentIdLanguage())
            ->where("idFrame", "=", $idFrame)
            ->all();
        $this->options = [];
        foreach ($icon as $i => $j) {
            foreach ($fes as $fe) {
                if ($fe->coreType == $i) {
                    $this->options[] = $fe;
                }
            }
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.checkbox.fe-frame');
    }
}
