<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CardError extends Component
{
    public string $title;
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $type,
        public string $message,
        public string $goto,
        public string $gotoLabel,
    )
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        if ($this->type == 'error') {
            $this->title = "Error";
        }
        if ($this->type == 'warning') {
            $this->title = "Warning";
        }
        if ($this->type == 'info') {
            $this->title = "Information";
        }
        return view('components.card-error');
    }
}
