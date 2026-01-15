<?php

namespace App\Data\Daisy;

class ComponentData
{
    public int $id;

    public ?int $idLemma = null;

    public array $fn = []; // possible grid functions

    public ?string $fnDef = null; // definitive grid function

    public string $word;

    public string $pos; // UPOS tag

    public ?int $idCluster = null;

    public bool $main = false;

    private int $head = 0;

    public array $lemmas = [];

    public function __construct(int $id, string $word, string $pos)
    {
        $this->id = $id;
        $this->word = $word;
        $this->pos = $pos;
    }

    public function head(?int $value = null): int
    {
        if ($value !== null) {
            $this->head = $value;
        }

        return $this->head;
    }
}
