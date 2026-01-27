<?php

namespace App\Services\CLN_RNT;

use App\Data\CLN\Confirmation;
use App\Models\CLN\BNode;
use App\Models\CLN\JNode;
use App\Models\CLN_RNT\L23Layer;
use App\Models\CLN_RNT\L5Layer;
use App\Services\CLN\Node;

/**
 * Ghost Manager
 *
 * Manages ghost nodes for null instantiation at L23 layer.
 * Ghost nodes represent predicted-but-not-realized elements (e.g., dropped subjects,
 * elided heads, implicit arguments).
 *
 * Ghost Node Processing:
 * 1. L5 node predicts a ghost node at L23 in next column
 * 2. On token arrival:
 *    - MATCH: Ghost activates, sends confirmation to previous column, creates L23 node
 *    - MISMATCH + MANDATORY: Ghost persists, creates L23 nodes for both ghost and actual token
 *    - MISMATCH + OPTIONAL: Ghost is deactivated
 *
 * Key responsibilities:
 * - Create ghost nodes from L5 predictions
 * - Check token match against ghost predictions
 * - Handle matched ghosts (confirmation + L23 node creation)
 * - Handle mismatched mandatory ghosts (persist + create both nodes)
 * - Handle mismatched optional ghosts (deactivate)
 * - Link created L23 nodes to source L5 constructions
 */
class GhostManager
{
    /**
     * Active ghost nodes indexed by position
     * Format: [position => [ghost1, ghost2, ...]]
     */
    private array $ghostsByPosition = [];

    /**
     * Node factory for creating L23 nodes
     */
    private NodeFactory $factory;

    /**
     * Create a new Ghost Manager
     *
     * @param  NodeFactory|null  $factory  Optional factory
     */
    public function __construct(?NodeFactory $factory = null)
    {
        $this->factory = $factory ?? new NodeFactory;
    }

    // ========================================================================
    // Ghost Node Creation
    // ========================================================================

    /**
     * Create a ghost node from L5 prediction
     *
     * Ghost nodes represent predicted elements that may not appear in input.
     * They can be mandatory (must be fulfilled) or optional (can be dropped).
     *
     * @param  L23Layer  $l23  Target L23 layer where ghost will appear
     * @param  int  $targetPosition  Column position for ghost
     * @param  string  $type  Ghost type (word, lemma, pos, feature)
     * @param  string  $value  Expected value
     * @param  bool  $mandatory  Whether ghost is mandatory
     * @param  int  $sourcePosition  Source column that predicted this ghost
     * @param  int  $constructionId  Construction ID that generated prediction
     * @return array Ghost metadata
     */
    public function createGhostNode(
        L23Layer $l23,
        int $targetPosition,
        string $type,
        string $value,
        bool $mandatory = false,
        int $sourcePosition = 0,
        int $constructionId = 0
    ): array {
        // Create ghost metadata
        $ghost = [
            'id' => "ghost_{$targetPosition}_{$type}_{$value}",
            'position' => $targetPosition,
            'type' => $type,  // word, lemma, pos, feature
            'value' => $value,
            'mandatory' => $mandatory,
            'source_position' => $sourcePosition,
            'construction_id' => $constructionId,
            'activated' => false,
            'matched' => false,
        ];

        // Track ghost at target position
        if (! isset($this->ghostsByPosition[$targetPosition])) {
            $this->ghostsByPosition[$targetPosition] = [];
        }
        $this->ghostsByPosition[$targetPosition][] = $ghost;

        return $ghost;
    }

    // ========================================================================
    // Token Processing
    // ========================================================================

    /**
     * Process token against ghosts at given position
     *
     * Checks if token matches any ghost predictions and handles accordingly.
     *
     * @param  L23Layer  $currentL23  Current column L23 layer
     * @param  L23Layer|null  $previousL23  Previous column L23 layer (for confirmation)
     * @param  L5Layer|null  $previousL5  Previous column L5 layer (for linking)
     * @param  int  $position  Current column position
     * @param  object  $token  Input token
     * @return array Processing results
     */
    public function processToken(
        L23Layer $currentL23,
        ?L23Layer $previousL23,
        ?L5Layer $previousL5,
        int $position,
        object $token
    ): array {
        $results = [
            'matched_ghosts' => [],
            'persisted_mandatory_ghosts' => [],
            'deactivated_optional_ghosts' => [],
            'created_l23_nodes' => [],
        ];

        // Get ghosts for this position
        $ghosts = $this->ghostsByPosition[$position] ?? [];

        foreach ($ghosts as &$ghost) {
            // Check if token matches ghost prediction
            $matches = $this->checkMatch($ghost, $token);

            if ($matches) {
                // CASE 1: Token matches ghost
                $result = $this->handleMatch(
                    $ghost,
                    $currentL23,
                    $previousL23,
                    $previousL5,
                    $token
                );
                $results['matched_ghosts'][] = $ghost;
                $results['created_l23_nodes'][] = $result['l23_node'];
            } else {
                // CASE 2 or 3: Token doesn't match
                if ($ghost['mandatory']) {
                    // CASE 2: Mandatory ghost - persist and create both nodes
                    $result = $this->handleMandatoryMismatch(
                        $ghost,
                        $currentL23,
                        $previousL23,
                        $previousL5,
                        $token
                    );
                    $results['persisted_mandatory_ghosts'][] = $ghost;
                    $results['created_l23_nodes'] = array_merge(
                        $results['created_l23_nodes'],
                        $result['l23_nodes']
                    );
                } else {
                    // CASE 3: Optional ghost - deactivate
                    $this->deactivateGhost($ghost);
                    $results['deactivated_optional_ghosts'][] = $ghost;
                }
            }
        }

        return $results;
    }

    /**
     * Check if token matches ghost prediction
     *
     * @param  array  $ghost  Ghost metadata
     * @param  object  $token  Input token
     * @return bool True if matches
     */
    private function checkMatch(array $ghost, object $token): bool
    {
        $type = $ghost['type'];
        $value = $ghost['value'];

        return match ($type) {
            'word' => ($token->form ?? '') === $value,
            'lemma' => ($token->lemma ?? '') === $value,
            'pos' => ($token->upos ?? '') === $value,
            'feature' => $this->checkFeatureMatch($token, $value),
            default => false,
        };
    }

    /**
     * Check if token has matching feature
     *
     * @param  object  $token  Input token
     * @param  string  $featurePattern  Feature pattern (e.g., "Number=Sing")
     * @return bool True if matches
     */
    private function checkFeatureMatch(object $token, string $featurePattern): bool
    {
        $features = $token->feats ?? '';

        return str_contains($features, $featurePattern);
    }

    // ========================================================================
    // Match Handlers
    // ========================================================================

    /**
     * Handle matched ghost (token matches prediction)
     *
     * Steps:
     * 1. Activate ghost
     * 2. Send confirmation to previous column
     * 3. Create L23 node in previous column for source
     * 4. Link created L23 node to L5 construction
     *
     * @param  array  $ghost  Ghost metadata (passed by reference)
     * @param  L23Layer  $currentL23  Current L23 layer
     * @param  L23Layer|null  $previousL23  Previous L23 layer
     * @param  L5Layer|null  $previousL5  Previous L5 layer
     * @param  object  $token  Matched token
     * @return array Result with created nodes
     */
    private function handleMatch(
        array &$ghost,
        L23Layer $currentL23,
        ?L23Layer $previousL23,
        ?L5Layer $previousL5,
        object $token
    ): array {
        // Step 1: Activate ghost
        $ghost['activated'] = true;
        $ghost['matched'] = true;

        $result = ['l23_node' => null, 'confirmation' => null];

        // Step 2 & 3: Send confirmation and create L23 node in previous column
        if ($previousL23 && $previousL5) {
            // Create L23 node in previous column equal to source
            $l23Node = $this->createL23NodeFromGhost($previousL23, $ghost, $token);
            $result['l23_node'] = $l23Node;

            // Step 4: Link L23 node to L5 construction
            $this->linkToConstruction($l23Node, $previousL5, $ghost['construction_id']);

            // Send confirmation (L23 → L23 lateral signal)
            $confirmation = new Confirmation(
                sourcePosition: $ghost['position'],
                targetPosition: $ghost['source_position'],
                type: $ghost['type'],
                value: $ghost['value'],
                strength: 1.0,  // Full match strength
                constructionId: $ghost['construction_id'],
                metadata: ['ghost_fulfilled' => true]
            );
            $confirmation->apply($previousL5);
            $result['confirmation'] = $confirmation;
        }

        return $result;
    }

    /**
     * Handle mandatory ghost mismatch
     *
     * When token doesn't match but ghost is mandatory:
     * 1. Persist ghost (execute as if matched)
     * 2. Create L23 node for ghost in previous column
     * 3. ALSO create L23 node for actual token in current column
     *
     * @param  array  $ghost  Ghost metadata (passed by reference)
     * @param  L23Layer  $currentL23  Current L23 layer
     * @param  L23Layer|null  $previousL23  Previous L23 layer
     * @param  L5Layer|null  $previousL5  Previous L5 layer
     * @param  object  $token  Actual token (not matching)
     * @return array Result with created nodes
     */
    private function handleMandatoryMismatch(
        array &$ghost,
        L23Layer $currentL23,
        ?L23Layer $previousL23,
        ?L5Layer $previousL5,
        object $token
    ): array {
        // Mark as activated but not matched
        $ghost['activated'] = true;
        $ghost['matched'] = false;

        $result = ['l23_nodes' => []];

        // Create ghost L23 node in previous column (as if matched)
        if ($previousL23 && $previousL5) {
            $ghostNode = $this->createL23NodeFromGhost($previousL23, $ghost, null);
            $this->linkToConstruction($ghostNode, $previousL5, $ghost['construction_id']);
            $result['l23_nodes'][] = $ghostNode;

            // Send confirmation for ghost
            $confirmation = new Confirmation(
                sourcePosition: $ghost['position'],
                targetPosition: $ghost['source_position'],
                type: $ghost['type'],
                value: $ghost['value'],
                strength: 0.8,  // Slightly lower for mismatch
                constructionId: $ghost['construction_id'],
                metadata: ['ghost_mandatory' => true, 'ghost_fulfilled' => true]
            );
            $confirmation->apply($previousL5);
        }

        // ALSO create L23 node for actual token in current column
        $actualNode = $this->createL23NodeFromToken($currentL23, $token);
        $result['l23_nodes'][] = $actualNode;

        return $result;
    }

    /**
     * Deactivate optional ghost
     *
     * @param  array  $ghost  Ghost metadata (passed by reference)
     */
    private function deactivateGhost(array &$ghost): void
    {
        $ghost['activated'] = false;
        $ghost['matched'] = false;
    }

    // ========================================================================
    // Node Creation Helpers
    // ========================================================================

    /**
     * Create L23 node from ghost metadata
     *
     * @param  L23Layer  $l23  Target L23 layer
     * @param  array  $ghost  Ghost metadata
     * @param  object|null  $token  Optional token (for matched ghosts)
     * @return Node Created node
     */
    private function createL23NodeFromGhost(
        L23Layer $l23,
        array $ghost,
        ?object $token
    ): Node {
        $type = $ghost['type'];
        $value = $ghost['value'];

        $node = match ($type) {
            'word' => $this->factory->createWordNode(
                word: $value,
                lemma: $token->lemma ?? $value,
                pos: $token->upos ?? '',
                id: "ghost_word_{$ghost['position']}_{$value}"
            ),
            'lemma' => $this->factory->createLemmaNode(
                lemma: $value,
                semanticType: 'E',
                id: "ghost_lemma_{$ghost['position']}_{$value}"
            ),
            'pos' => $this->factory->createPOSNode(
                pos: $value,
                id: "ghost_pos_{$ghost['position']}_{$value}"
            ),
            'feature' => $this->factory->createFeatureNode(
                feature: $value,
                id: "ghost_feature_{$ghost['position']}_{$value}"
            ),
        };

        // Mark as ghost node in metadata
        $node->metadata['is_ghost'] = true;
        $node->metadata['ghost_id'] = $ghost['id'];
        $node->metadata['ghost_mandatory'] = $ghost['mandatory'];

        $l23->addNode($node);

        return $node;
    }

    /**
     * Create L23 node from actual token
     *
     * @param  L23Layer  $l23  Target L23 layer
     * @param  object  $token  Input token
     * @return Node Created node
     */
    private function createL23NodeFromToken(L23Layer $l23, object $token): Node
    {
        $node = $this->factory->createWordNode(
            word: $token->form ?? '',
            lemma: $token->lemma ?? '',
            pos: $token->upos ?? ''
        );

        $l23->addNode($node);

        return $node;
    }

    /**
     * Link L23 node to L5 construction
     *
     * Creates connection from L23 node to L5 construction to confirm/complete pattern.
     *
     * @param  Node  $l23Node  L23 node to link
     * @param  L5Layer  $l5  L5 layer containing construction
     * @param  int  $constructionId  Construction ID to link to
     */
    private function linkToConstruction(
        Node $l23Node,
        L5Layer $l5,
        int $constructionId
    ): void {
        // Find construction node in L5
        $constructions = $l5->getNodesByType('construction');
        foreach ($constructions as $construction) {
            if (($construction->metadata['construction_id'] ?? 0) === $constructionId) {
                // Create L23 → L5 link
                $l23Node->connectTo($construction);
                break;
            }
        }

        // Also check partial constructions
        $partials = $l5->getPartialConstructions();
        foreach ($partials as $partial) {
            if (($partial->metadata['construction_id'] ?? 0) === $constructionId) {
                // Create L23 → L5 link
                $l23Node->connectTo($partial);
                break;
            }
        }
    }

    // ========================================================================
    // Ghost Introspection
    // ========================================================================

    /**
     * Get all ghosts at a specific position
     *
     * @param  int  $position  Column position
     * @return array Array of ghost metadata
     */
    public function getGhostsAtPosition(int $position): array
    {
        return $this->ghostsByPosition[$position] ?? [];
    }

    /**
     * Get all active ghosts across all positions
     *
     * @return array Array of ghost metadata
     */
    public function getActiveGhosts(): array
    {
        $active = [];
        foreach ($this->ghostsByPosition as $position => $ghosts) {
            foreach ($ghosts as $ghost) {
                if ($ghost['activated']) {
                    $active[] = $ghost;
                }
            }
        }

        return $active;
    }

    /**
     * Get all matched ghosts
     *
     * @return array Array of ghost metadata
     */
    public function getMatchedGhosts(): array
    {
        $matched = [];
        foreach ($this->ghostsByPosition as $position => $ghosts) {
            foreach ($ghosts as $ghost) {
                if ($ghost['matched']) {
                    $matched[] = $ghost;
                }
            }
        }

        return $matched;
    }

    /**
     * Clear all ghosts
     */
    public function reset(): void
    {
        $this->ghostsByPosition = [];
    }
}
