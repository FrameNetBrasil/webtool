<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use App\Repositories\RelationType;
use App\Services\AppService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class MicroframeRelation extends Component
{
    public array $options;
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $group,
        public ?string $value = ''
    )
    {
        $idLanguage = AppService::getCurrentIdLanguage();
        $groupEntries = [
            'ontological' => 'sty_mf_type_ontological',
            'subsumption' => 'sty_mf_type_subsumption',
        ];

        $relations = Criteria::table("view_microframe as mf")
            ->join("view_relation as r", "mf.idEntity", "=", "r.idEntity1")
            ->join("semantictype as st", "r.idEntity2", "=", "st.idEntity")
            ->where("st.entry", $groupEntries[$group])
            ->where('mf.idLanguage', $idLanguage)
            ->select("mf.idEntity","mf.entry","mf.name")
            ->all();
        $this->options = [];
        foreach($relations as $relation) {
            $this->options[] = [
                'value' => $relation->idEntity,
                'entry' => $relation->entry,
                'name' => $relation->name,
                'color' => 1,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.microframe-relation');
    }
}
