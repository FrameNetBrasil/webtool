<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Tabs extends Component
{
    public string $active;
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public array $tabs,
        public array $slots,
        public ?string $onSelect = ''
    )
    {
        $this->active = array_keys($tabs)[0];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.tabs');
    }
}
