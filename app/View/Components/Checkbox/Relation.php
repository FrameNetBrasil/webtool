<?php

namespace App\View\Components\Checkbox;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Relation extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $label,
        public array  $relations = []
    )
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.checkbox.relation');
    }
}
