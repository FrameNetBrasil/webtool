<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use App\Repositories\Frame;
use App\Repositories\FrameElement;
use App\Services\FrameService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;


class CeCxn extends Component
{
    public array $options;
    public string $default = '';

    /**
     * Create a new component instance.
     */
    public function __construct(
        public int     $idConstruction,
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
        if ($idConstruction > 0) {
            $filter = [["idConstruction", "=", $idConstruction]];
            $ces = Criteria::byFilterLanguage("view_constructionelement", $filter)->all();
            if ($this->hasNull) {
                $this->options[] = [
                    'idConstructionElement' => '-1',
                    'name' => $this->nullName ?? "NULL",
                    'idColor' => "color_1"
                ];
            }
            foreach ($ces as $ce) {
                if ($this->value == $ce->idConstructionElement) {
                    $this->default = $ce->name;
                }
                $this->options[] = [
                    'idConstructionElement' => $ce->idConstructionElement,
                    'name' => $ce->name,
                    'idColor' => $ce->idColor
                ];
            }

        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.ce-cxn');
    }
}
