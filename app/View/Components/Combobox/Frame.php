<?php

namespace App\View\Components\Combobox;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Repositories\Frame as FrameRepository;

class Frame extends Component
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
            $frame = FrameRepository::byId($this->value);
            $this->placeholder = $frame->name;
        } else {
            $this->placeholder = "Search Frame";
        }
        if ($this->idName == '') {
            $this->idName = $this->id;
        }
        $this->description = $this->hasDescription ? 'description' : '';
        return view('components.combobox.frame');
    }
}
