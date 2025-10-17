<?php

namespace App\View\Components\Combobox;

use App\Repositories\Lexicon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LexiconLemma extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $placeholder = '',
        public string $name = '',
        public ?int $value = 0,
    )
    {
        if (($this->value != '') && ($this->value != 0)) {
            $lemma = Lexicon::lemmabyId($this->value);
            $this->placeholder = $lemma->shortName;
        } else {
            $this->placeholder = "Search Lemma";
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.lexicon-lemma');
    }
}
