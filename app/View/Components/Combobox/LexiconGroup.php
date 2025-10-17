<?php

namespace App\View\Components\Combobox;

use App\Database\Criteria;
use App\Services\AppService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class LexiconGroup extends Component
{
    /**
     * Create a new component instance.
     */
    public array $options;
    public function __construct(
        public string $id,
        public string $label,
        public ?int $value = null
    )
    {
        $list = Criteria::table("lexicon_group")
            ->orderBy("name")->all();
        $this->options = [];
        foreach($list as $item) {
            $this->options[$item->idLexiconGroup] = [
                'id' => $item->idLexiconGroup,
                'text' => $item->name,
            ];
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.combobox.lexicon-group');
    }
}
