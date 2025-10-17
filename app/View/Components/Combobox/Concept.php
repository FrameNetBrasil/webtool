<?php

namespace App\View\Components\Combobox;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Repositories\Frame as FrameRepository;

class Concept extends Component
{
    /**
     * Create a new component instance.
     */
    public ?string $description = '';
    public function __construct(
        public string $id,
        public string $label,
        public ?int $value = null,
        public string $placeholder = '',
        public string $name = '',
        public string $idName = '',
        public ?string $onChange = '',
        public ?string $onSelect = '',
        public ?bool $hasDescription = true,
    )
    {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        if (($this->value != '') && ($this->value != 0)) {
            $concept = \App\Repositories\Concept::byId($this->value);
            $this->placeholder = $concept->name;
        } else {
            $this->placeholder = "Search Concept";
        }
        if ($this->idName == '') {
            $this->idName = $this->id;
        }
        return view('components.combobox.concept');
    }
}
