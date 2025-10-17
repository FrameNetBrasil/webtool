<?php

namespace App\View\Components\Element;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Ce extends Component
{
    /**
     * Create a new component instance.
     */
    public string $icon;

    public function __construct(
        public string $name = '',
        public string $idColor = '',
    )
    {
        //$this->icon = config("webtool.fe.icon.grid")[$this->type];
        $this->icon = "black circle";
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.element.ce');
    }
}
