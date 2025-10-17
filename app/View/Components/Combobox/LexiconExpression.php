<?php

namespace App\View\Components\Combobox;

use App\Repositories\Lexicon;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LexiconExpression extends Component
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
        debug('value',$this->value);
        if (($this->value != '') && ($this->value != 0)) {
            $lexicon = Lexicon::byId($this->value);
            $this->placeholder = $lexicon->name;
        } else {
            $this->placeholder = "Search Form";
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.lexicon-expression');
    }
}
