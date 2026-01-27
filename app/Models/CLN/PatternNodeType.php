<?php

namespace App\Models\CLN;

/**
 * These represent the grammar specification that you already have.
 */

enum PatternNodeType: string {
    case LITERAL = 'LITERAL';  // Specific word
    case POS = 'POS';          // Part-of-speech category
    case OR = 'OR';            // Alternative elements
    case AND = 'AND';          // Sequential conjunction (left then right)
    case VIP = 'VIP';
    case SOM = 'SOM';
}
