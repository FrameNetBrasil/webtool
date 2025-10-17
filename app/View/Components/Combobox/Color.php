<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Repositories\Color as ColorRepository;

class Color extends Component
{
    public array $options;
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $value = '',
        public string $defaultText = '',
    )
    {
        $this->defaultText = '';
        $list = Criteria::table("color")->orderBy("rgbBg")->all();
        $this->options = [];
        foreach($list as $c) {
            if ($this->value == $c->idColor) {
                $this->defaultText = $c->name;
            }
            $this->options[] = [
                'id' => $c->idColor,
                'text' => $c->name,
                'color' => "color_{$c->idColor}"
            ];
        }

    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.color');
    }
}
