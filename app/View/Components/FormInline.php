<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FormInline extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id = '',
//        public string $title,
//        public bool $center,
        public bool $border = false,
//        public string $toolbar = '',
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
        return view('components.form-inline');
    }
}
