<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use App\Repositories\SemanticType;
use App\Services\AppService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FrameScenario extends Component
{
    public array $options;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $value,
        public string $label = '',
        public string $placeholder = ''
    )
    {
        $scenarios = Criteria::table("view_relation as r")
            ->join("view_frame as f","r.idEntity1","=","f.idEntity")
            ->join("semantictype as st","r.idEntity2","=","st.idEntity")
            ->where("f.idLanguage","=", AppService::getCurrentIdLanguage())
            ->where("st.entry","=","sty_ft_scenario")
            ->select("f.idFrame","f.idEntity","f.name")
            ->orderby("f.name")
            ->all();
        $this->options = [];
        foreach ($scenarios as $scenario) {
            $this->options[] = (object)[
                'idFrame' => $scenario->idFrame,
                'name' => $scenario->name,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.frame-scenario');
    }
}
