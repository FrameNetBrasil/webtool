<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use App\Repositories\Qualia;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class QualiaRelations extends Component
{
    /**
     * Create a new component instance.
     */
    public array $options = [];
    public function __construct(
        public string $id,
        public string $label
    )
    {
        $list = Criteria::byFilterLanguage("view_qualia",[])
            ->orderBy("type")->orderBy("name")
            ->all();
        $this->options = [];
        foreach($list as $qualia) {
            $this->options[$qualia->type][] = [
                'idQualia' => $qualia->idQualia,
                'category' => ucFirst($qualia->type),
                'name' => $qualia->name,
                'frame' => $qualia->frameName,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.qualia-relations');
    }
}
