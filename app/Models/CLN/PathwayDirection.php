<?php

namespace App\Models\CLN;

enum PathwayDirection: string {
    case FEEDFORWARD = 'feedforward';  // Lower to higher (errors go up)
    case FEEDBACK = 'feedback';        // Higher to lower (predictions go down)
    case LATERAL = 'lateral';          // Same level
}
