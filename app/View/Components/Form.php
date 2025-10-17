<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Form extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id = '',
        public bool $center = false,
        public bool $border = true,
        public string $title = '',
        public string $toolbar = '',
//        public string $fields,
//        public string $buttons,
    )
    {
        if ($this->id === '') {
            $this->id = uniqid();
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.form');
    }
}
