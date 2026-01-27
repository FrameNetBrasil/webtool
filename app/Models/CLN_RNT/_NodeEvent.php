<?php

namespace App\Models\CLN_RNT;

/**
 * Node Event Types
 *
 * Defines the types of events that nodes can emit and subscribe to
 * in the CLN event-driven architecture.
 *
 * These events enable node-to-node communication without column orchestration,
 * supporting the transformation from column-centric to node-centric processing.
 */
enum NodeEvent: string
{
    /**
     * Node has been activated
     *
     * Emitted when:
     * - BNode receives activation from input
     * - JNode threshold is reached
     * - L23 nodes are created from token input
     */
    case ACTIVATED = 'activated';

    /**
     * Predicted node has been confirmed by matching input
     *
     * Emitted when:
     * - Predicted node matches incoming token
     * - confirmPrediction() is called
     */
    case PREDICTION_CONFIRMED = 'prediction_confirmed';

    /**
     * Partial construction has become complete
     *
     * Emitted when:
     * - All pattern elements have been matched
     * - Partial construction transitions to confirmed state
     */
    case CONSTRUCTION_CONFIRMED = 'construction_confirmed';

    /**
     * Partial construction has matched one more element
     *
     * Emitted when:
     * - tryAdvance() successfully matches next pattern element
     * - Partial's matched array is updated
     */
    case PATTERN_ADVANCED = 'pattern_advanced';

    /**
     * L5 construction has created L23 feedback node
     *
     * Emitted when:
     * - Confirmed construction creates L23 construction node
     * - Construction feedback loop completes
     */
    case L23_FEEDBACK_CREATED = 'l23_feedback_created';
}
