<?php

namespace App\View\Components\Combobox;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Repositories\Corpus as CorpusRepository;

class Document extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $label,
        public ?string $value = null,
        public string $placeholder = '',
        public ?string $onChange = '',
        public ?string $onSelect = '',
    )
    {
        $this->placeholder = "Search Document";
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.document');
    }
}
