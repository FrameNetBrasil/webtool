<?php

namespace App\View\Components\Combobox;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Enums\SearchOptions as EnumSearchOptions;

class SearchOptions extends Component
{
    /**
     * Create a new component instance.
     */
    public array $options = [];
    public function __construct(
        public string $id,
        public ?EnumSearchOptions $value = null,
        public string $label = '',
    )
    {
        $this->options[EnumSearchOptions::STARTSWITH->value] = EnumSearchOptions::STARTSWITH;
        $this->options[EnumSearchOptions::CONTAINS->value] = EnumSearchOptions::CONTAINS;
        $this->options[EnumSearchOptions::EXACT->value] = EnumSearchOptions::EXACT;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.search-options');
    }
}
