<?php

namespace App\View\Components\Combobox;

use App\Repositories\TypeInstance;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FeCoreness extends Component
{
    public array $options;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $value = '',
    )
    {
        $coreness = config('webtool.fe.coreness');
        $this->options = [];
        foreach ($coreness as $entry => $coreType) {
//            $this->options[] = [
//                'id' => $entry,
//                'name' => $coreType
//            ];
            $this->options[$entry] = $coreType;

        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.fe-coreness');
    }
}
