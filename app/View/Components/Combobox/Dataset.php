<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Dataset extends Component
{
    /**
     * Create a new component instance.
     */
    public array $options;
    public function __construct(
        public string $id,
        public string $label,
        public int $value
    )
    {
        $list = Criteria::table("dataset")->orderBy("name")->all();
        $this->options = [];
        foreach($list as $d) {
            $this->options[$d->idDataset] = [
                'id' => $d->idDataset,
                'text' => $d->name,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.dataset');
    }
}
