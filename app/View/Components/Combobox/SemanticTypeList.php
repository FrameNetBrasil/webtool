<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use App\Services\AppService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Component;
use App\Repositories\SemanticType as SemanticTypeRepository;

class SemanticTypeList extends Component
{
    public $list;
    public array $options;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $placeholder = '',
        public string $value = ''
    )
    {
        $list = Criteria::table("view_domain_semantictype as dst")
            ->join("semantictype as st", "st.idEntity", "=", "dst.stIdEntity")
            ->select("st.idSemanticType","dst.stName","dst.domainName")
            ->where("dst.idLanguage", "=", AppService::getCurrentIdLanguage())
            ->orderBy("dst.stName")
            ->all();
        $this->options = [];
        foreach ($list as $st) {
            $this->options[$st->idSemanticType] = [
                'id' => $st->idSemanticType,
                'text' => $st->stName . ' ['. $st->domainName . ']'
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.semantic-type-list');
    }
}
