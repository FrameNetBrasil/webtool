<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LayerGroup extends Component
{
    /**
     * Create a new component instance.
     */
    public array $options;
    public function __construct(
        public string $id,
        public string $label,
        public int $value
    )
    {
        $list = Criteria::table("layergroup")->orderBy("name")->all();
        $this->options = [];
        foreach($list as $lg) {
            $this->options[$lg->idLayerGroup] = [
                'id' => $lg->idLayerGroup,
                'text' => $lg->name,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.layer-group');
    }
}
