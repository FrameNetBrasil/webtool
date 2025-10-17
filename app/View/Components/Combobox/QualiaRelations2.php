<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use App\Repositories\Qualia;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class QualiaRelations2 extends Component
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
        $this->options = Criteria::byFilter("qualiarelation",[])
            ->orderBy("name")
            ->select("idQualiaRelation","name")
            ->keyBy("idQualiaRelation")
            ->all();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.qualia-relations-2');
    }
}
