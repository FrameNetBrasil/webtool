<?php

namespace App\View\Components\Combobox;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Repositories\Language as LanguageRepository;

class Language extends Component
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
        $this->options = LanguageRepository::listForSelection();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.language');
    }
}
