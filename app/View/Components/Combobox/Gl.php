<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;


class Gl extends Component
{
    public array $options;
    public string $default = '';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public int     $idLayerType,
        public string  $id = '',
        public string  $label = '',
        public ?string $value = null,
        public ?string $name = null,
        public ?string $nullName = null,
        public bool    $hasNull = false,
        public ?string  $defaultText = '',
        public ?string $onChange = null,
    )
    {
        if (is_null($this->name)) {
            $this->name = $this->id;
        }
        $this->value = $this->value ?? $this->nullName ?? '';
        $this->options = [];
        if ($idLayerType > 0) {
            $filter = [["idLayerType", "=", $idLayerType]];
            $gls = Criteria::byFilterLanguage("genericlabel", $filter)->all();
            if ($this->hasNull) {
                $this->options[] = [
                    'idGenericLabel' => '-1',
                    'name' => $this->nullName ?? "NULL",
                    'idColor' => "color_1"
                ];
            }
            foreach ($gls as $gl) {
                if ($this->value == $gl->idGenericLabel) {
                    $this->default = $gl->name;
                }
                $this->options[] = [
                    'idGenricLabel' => $gl->idGenericLabel,
                    'name' => $gl->name,
                    'idColor' => $gl->idColor
                ];
            }
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.gl');
    }
}
