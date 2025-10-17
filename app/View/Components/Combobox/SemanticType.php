<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use App\Services\AppService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Component;
use App\Repositories\SemanticType as SemanticTypeRepository;

class SemanticType extends Component
{
    public $list;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $placeholder = '',
        public string $root = ''
    )
    {
        $result = [];
        if ($root == '') {
            $list = Criteria::table("view_domain_semantictype as dst")
                ->join("semantictype as st", "st.idEntity", "=", "dst.stIdEntity")
                ->selectRaw("st.idSemanticType, concat(dst.stName,':',dst.domainName) as name")
                ->where("dst.idLanguage", "=", AppService::getCurrentIdLanguage())
                ->orderBy("dst.stName")
                ->all();
            foreach ($list as $row) {
                $result[] = [
                    'idSemanticType' => $row->idSemanticType,
                    'name' => $row->name,
                    'html' => view('components.element.semantictype', ['name' => $row->name])->render(),
                    'state' => 'open',
                    'iconCls' => ''
                ];
            }
        } else {
            $list = $this->buildTree($root);
            foreach ($list as $i => $row) {
                $node = (array)$row;
                $children = $this->buildTree($row['name']);
                $node['children'] = !empty($children) ? $children : null;
                $result[] = $node;
            }
        }
        $this->list = $result;
    }

    public function buildTree(string $root): array
    {
        $st = Criteria::byFilterLanguage("view_semantictype", ["name", "=", $root])->first();
        $list = SemanticTypeRepository::listChildren($st->idEntity);
        $result = [];
        foreach ($list as $row) {
            $result[] = [
                'idSemanticType' => $row->idSemanticType,
                'name' => $row->name,
                'html' => view('components.element.semantictype', ['name' => $row->name])->render(),
                'state' => 'open',
                'iconCls' => ''
            ];
        }
        return $result;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.semantic-type');
    }
}
