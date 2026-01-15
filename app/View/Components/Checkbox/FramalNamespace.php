<?php

namespace App\View\Components\Checkbox;

use App\Database\Criteria;
use App\Repositories\Frame;
use App\Services\AppService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FramalNamespace extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $label,
        public int $idFrame,
        public array $options = [],
        public string $type = 'frame'
    ) {
        $frame = Frame::byId($this->idFrame);
        $query = Criteria::table('view_namespace')
            ->where('idLanguage', AppService::getCurrentIdLanguage())
            ->orderBy('name');
        if ($type == 'frame') {
            $query = $query->whereNotIn('nameEn', ['Class', 'Microframe', 'Cluster']);
        }
        $namespaces = $query->all();
        $this->options = [];
        foreach ($namespaces as $namespace) {
            $this->options[] = [
                'value' => $namespace->idNamespace,
                'name' => $namespace->name,
                'checked' => ($frame->idNamespace == $namespace->idNamespace) ? 'checked' : '',
                'disable' => false,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.checkbox.framal-namespace');
    }
}
