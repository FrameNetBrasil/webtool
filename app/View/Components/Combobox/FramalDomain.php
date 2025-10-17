<?php

namespace App\View\Components\Combobox;

use App\Repositories\SemanticType;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FramalDomain extends Component
{
    public $options;
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $value = '',
        public string $label = '',
        public string $placeholder = ''
    )
    {
        $domains = SemanticType::listFrameDomain()->all();
        $this->options = [[
            'idSemanticType' => null,
            'name' => '-- all --'
        ]];
        foreach ($domains as $domain) {
            $this->options[] = $domain;
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.framal-domain');
    }
}
