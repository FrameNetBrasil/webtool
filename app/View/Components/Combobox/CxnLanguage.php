<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class CxnLanguage extends Component
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
        $this->options = Criteria::table("construction")
            ->join("language", "language.idLanguage", "=", "construction.idLanguage")
            ->select("language.idLanguage", "language.description as language")
            ->distinct()
            ->orderBy("language.description")
            ->chunkResult("idLanguage","language");
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.cxn-language');
    }
}
