<?php

namespace App\Models\CLN;

class ReferenceFrame
{
    public string $id;                    // Unique identifier
    public string $constructionType;      // 'MAIN_CLAUSE', 'RELATIVE_CLAUSE', 'NP', etc.
    public int $position = 0;             // Current position in this frame
    public ?ReferenceFrame $parent;       // Enclosing frame (for embeddings)
    public array $boundConstituents = []; // What's been bound in this frame
}
