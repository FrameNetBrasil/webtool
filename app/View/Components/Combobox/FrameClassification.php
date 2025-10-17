<?php

namespace App\View\Components\Combobox;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class FrameClassification extends Component
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
        $this->options = [
            ['text' => 'plain list', 'value' => ''],
            ['text' => 'by Cluster', 'value' => 'cluster'],
            ['text' => 'by Domain', 'value' => 'domain'],
            ['text' => 'by Type', 'value' => 'type'],
        ];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.frame-classification');
    }
}
