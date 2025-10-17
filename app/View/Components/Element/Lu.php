<?php

namespace App\View\Components\Element;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Lu extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $name = '',
        public ?string $frame = null,
    )
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.element.lu');
    }
}
