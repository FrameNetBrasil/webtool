<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ProjectGroup extends Component
{
    /**
     * Create a new component instance.
     */
    public array $options;
    public function __construct(
        public string $id,
        public string $label,
        public int $value
    )
    {
        $list = Criteria::table("projectgroup")->orderBy("name")->all();
        $this->options = [];
        foreach($list as $pg) {
            $this->options[$pg->idProjectGroup] = [
                'id' => $pg->idProjectGroup,
                'text' => $pg->name,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.project-group');
    }
}
