<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $label = '',
        public string $color = 'primary',
        public string $href = '',
        public string $icon = ''
    )
    {
        $this->color = $color;//ucfirst($color);
        if ($this->color === 'danger') {
            $this->color = 'negative';
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.button');
    }
}
