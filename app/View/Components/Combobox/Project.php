<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Project extends Component
{
    /**
     * Create a new component instance.
     */
    public array $options;
    public function __construct(
        public string $id,
        public string $label,
        public ?int $value = null
    )
    {
        $list = Criteria::table("project")->orderBy("name")->all();
        $this->options = [];
        foreach($list as $p) {
            $this->options[$p->idProject] = [
                'id' => $p->idProject,
                'text' => $p->name,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.project');
    }
}
