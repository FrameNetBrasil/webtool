<?php

namespace App\View\Components\Element;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class GenericLabel extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $name = '',
        public string $idColor = '',
    )
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.element.generic-label');
    }
}
