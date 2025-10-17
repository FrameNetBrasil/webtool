<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class UdPos extends Component
{
    public array $options;
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $value,
        public string $label = '',
        public string $placeholder = ''
    )
    {
        //$pos = new \App\Repositories\POS();
        $this->options = Criteria::table("udpos")
            ->orderBy("POS")
            ->all();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.ud-pos');
    }
}
