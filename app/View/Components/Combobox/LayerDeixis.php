<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use App\Services\AppService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LayerDeixis extends Component
{
    /**
     * Create a new component instance.
     */
    public array $options;
    public function __construct(
        public string $id,
        public string $label,
        public int $value,
        public ?string $onChange = null
    )
    {
        $list = Criteria::table("view_layertype as lt")
            ->join("layergroup as lg", "lg.idLayerGroup", "=", "lt.idLayerGroup")
            ->select("lt.idLayerType", "lg.name as layerGroup", "lt.name")
            ->where("lg.type", "Deixis")
            ->where("lt.idLanguage", AppService::getCurrentIdLanguage())
            ->orderBy("lg.name")
            ->orderBy("lt.name")->all();
        $this->options = [];
        foreach($list as $lt) {
            $this->options[$lt->idLayerType] = [
                'id' => $lt->idLayerType,
                'text' => $lt->name,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.layer-deixis');
    }
}
