<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use App\Services\AppService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RelationGroup extends Component
{
    /**
     * Create a new component instance.
     */
    public array $options;
    public function __construct(
        public string $id,
        public string $label,
        public ?int $value = null
    )
    {
        $list = Criteria::table("view_relationgroup")
            ->where("idLanguage",AppService::getCurrentIdLanguage())
            ->orderBy("name")->all();
        $this->options = [];
        foreach($list as $item) {
            $this->options[$item->idRelationGroup] = [
                'id' => $item->idRelationGroup,
                'text' => $item->name,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.relation-group');
    }
}
