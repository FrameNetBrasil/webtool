<?php

namespace App\View\Components\Combobox;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Options extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $value,
        public array $options,
        public string $label = '',
        public string $placeholder = '',
    )
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.options');
    }
}
