<?php

namespace App\Models\CLN;

/**
 * Mode of operation for SOM inhibitors.
 */
enum SOMMode: string {
    case AND = 'AND';           // Releases when specific element arrives
    case OR_ACCUMULATE = 'OR';  // Releases when unexpected element arrives
}
