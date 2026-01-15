<?php

namespace App\Models\Parser;

/**
 * ParseGraph - The final output of the three-stage parsing pipeline
 *
 * Represents the complete syntactic structure of a sentence with nodes and edges.
 * Biological analogy: Fully folded protein with all bonds (peptide + disulfide).
 */
class ParseGraph
{
    /** @var ClausalCENode[] */
    private array $nodes = [];

    /** @var Dependency[] */
    private array $edges = [];

    /** @var Dependency[] */
    private array $nonProjectiveEdges = [];

    /** @var SententialCENode[] */
    private array $sententialCEs = [];

    private ?ClausalCENode $root = null;

    public function __construct() {}

    /**
     * Add a node to the graph
     */
    public function addNode(ClausalCENode $node): void
    {
        $this->nodes[] = $node;
    }

    /**
     * Add multiple nodes to the graph
     *
     * @param  ClausalCENode[]  $nodes
     */
    public function addNodes(array $nodes): void
    {
        foreach ($nodes as $node) {
            $this->addNode($node);
        }
    }

    /**
     * Add an edge (dependency) to the graph
     */
    public function addEdge(Dependency $edge): void
    {
        $this->edges[] = $edge;

        if ($edge->isNonProjective) {
            $this->nonProjectiveEdges[] = $edge;
        }
    }

    /**
     * Add multiple edges to the graph
     *
     * @param  Dependency[]  $edges
     */
    public function addEdges(array $edges): void
    {
        foreach ($edges as $edge) {
            $this->addEdge($edge);
        }
    }

    /**
     * Mark an edge as non-projective
     */
    public function markNonProjective(Dependency $edge): void
    {
        $edge->isNonProjective = true;
        if (! in_array($edge, $this->nonProjectiveEdges, true)) {
            $this->nonProjectiveEdges[] = $edge;
        }
    }

    /**
     * Set the root node
     */
    public function setRoot(ClausalCENode $root): void
    {
        $this->root = $root;
    }

    /**
     * Get the root node
     */
    public function getRoot(): ?ClausalCENode
    {
        return $this->root;
    }

    /**
     * Get all nodes
     *
     * @return ClausalCENode[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * Get all edges
     *
     * @return Dependency[]
     */
    public function getEdges(): array
    {
        return $this->edges;
    }

    /**
     * Get non-projective edges
     *
     * @return Dependency[]
     */
    public function getNonProjectiveEdges(): array
    {
        return $this->nonProjectiveEdges;
    }

    /**
     * Check if graph has non-projective edges
     */
    public function hasNonProjectiveEdges(): bool
    {
        return ! empty($this->nonProjectiveEdges);
    }

    /**
     * Add sentential CE nodes
     *
     * @param  SententialCENode[]  $sententialCEs
     */
    public function setSententialCEs(array $sententialCEs): void
    {
        $this->sententialCEs = $sententialCEs;
    }

    /**
     * Get sentential CE nodes
     *
     * @return SententialCENode[]
     */
    public function getSententialCEs(): array
    {
        return $this->sententialCEs;
    }

    /**
     * Find a node by word
     */
    public function findNode(string $word): ?ClausalCENode
    {
        foreach ($this->nodes as $node) {
            if ($node->getWord() === $word) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Find a node by index
     */
    public function findNodeByIndex(int $index): ?ClausalCENode
    {
        foreach ($this->nodes as $node) {
            if ($node->getIndex() === $index) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Get all root candidates (nodes with no incoming edges)
     *
     * @return ClausalCENode[]
     */
    public function getRoots(): array
    {
        $hasIncoming = [];

        foreach ($this->edges as $edge) {
            $hasIncoming[$edge->dependent->getIndex()] = true;
        }

        return array_filter($this->nodes, fn ($n) => ! isset($hasIncoming[$n->getIndex()]));
    }

    /**
     * Check if all nodes are reachable from root
     */
    public function isFullyConnected(): bool
    {
        if (! $this->root) {
            return false;
        }

        $visited = [];
        $this->dfs($this->root, $visited);

        return count($visited) === count($this->nodes);
    }

    /**
     * Depth-first search traversal
     */
    private function dfs(ClausalCENode $node, array &$visited): void
    {
        $visited[$node->getIndex()] = $node;

        foreach ($this->edges as $edge) {
            if ($edge->governor === $node && ! isset($visited[$edge->dependent->getIndex()])) {
                $this->dfs($edge->dependent, $visited);
            }
        }
    }

    /**
     * Get average dependency length
     */
    public function getAvgDependencyLength(): float
    {
        if (empty($this->edges)) {
            return 0.0;
        }

        $total = array_sum(array_map(fn ($e) => $e->getLength(), $this->edges));

        return $total / count($this->edges);
    }

    /**
     * Get maximum dependency length
     */
    public function getMaxDependencyLength(): int
    {
        if (empty($this->edges)) {
            return 0;
        }

        return max(array_map(fn ($e) => $e->getLength(), $this->edges));
    }

    /**
     * Get dependents of a specific node
     *
     * @return ClausalCENode[]
     */
    public function getDependents(ClausalCENode $node): array
    {
        $dependents = [];

        foreach ($this->edges as $edge) {
            if ($edge->governor === $node) {
                $dependents[] = $edge->dependent;
            }
        }

        return $dependents;
    }

    /**
     * Get governor of a specific node
     */
    public function getGovernor(ClausalCENode $node): ?ClausalCENode
    {
        foreach ($this->edges as $edge) {
            if ($edge->dependent === $node) {
                return $edge->governor;
            }
        }

        return null;
    }

    /**
     * Get all edges from a specific node
     *
     * @return Dependency[]
     */
    public function getOutgoingEdges(ClausalCENode $node): array
    {
        return array_filter($this->edges, fn ($e) => $e->governor === $node);
    }

    /**
     * Get all edges to a specific node
     *
     * @return Dependency[]
     */
    public function getIncomingEdges(ClausalCENode $node): array
    {
        return array_filter($this->edges, fn ($e) => $e->dependent === $node);
    }

    /**
     * Compute statistics about the parse graph
     */
    public function getStatistics(): array
    {
        return [
            'num_nodes' => count($this->nodes),
            'num_edges' => count($this->edges),
            'num_non_projective' => count($this->nonProjectiveEdges),
            'avg_dependency_length' => $this->getAvgDependencyLength(),
            'max_dependency_length' => $this->getMaxDependencyLength(),
            'is_fully_connected' => $this->isFullyConnected(),
            'num_clauses' => count($this->sententialCEs),
        ];
    }

    /**
     * Convert to array for storage/serialization
     */
    public function toArray(): array
    {
        return [
            'nodes' => array_map(fn (ClausalCENode $n) => $n->toArray(), $this->nodes),
            'edges' => array_map(fn (Dependency $e) => $e->toArray(), $this->edges),
            'root_index' => $this->root?->getIndex(),
            'sentential_ces' => array_map(fn (SententialCENode $s) => $s->toArray(), $this->sententialCEs),
            'statistics' => $this->getStatistics(),
        ];
    }

    /**
     * Get a text representation of the parse structure
     */
    public function toText(): string
    {
        $lines = [];

        // Sort nodes by index
        $sortedNodes = $this->nodes;
        usort($sortedNodes, fn ($a, $b) => $a->getIndex() <=> $b->getIndex());

        $lines[] = 'Sentence: '.implode(' ', array_map(fn ($n) => $n->getWord(), $sortedNodes));
        $lines[] = '';
        $lines[] = 'Nodes:';

        foreach ($sortedNodes as $node) {
            $marker = $node === $this->root ? ' [ROOT]' : '';
            $lines[] = sprintf(
                '  %d: %s (%s / %s)%s',
                $node->getIndex(),
                $node->getWord(),
                $node->phrasalNode->phrasalCE->value,
                $node->clausalCE->value,
                $marker
            );
        }

        $lines[] = '';
        $lines[] = 'Edges:';

        foreach ($this->edges as $edge) {
            $marker = $edge->isNonProjective ? ' [NON-PROJ]' : '';
            $lines[] = sprintf(
                '  %s --%s--> %s (%.2f)%s',
                $edge->governor->getWord(),
                $edge->relation,
                $edge->dependent->getWord(),
                $edge->strength,
                $marker
            );
        }

        if (! empty($this->sententialCEs)) {
            $lines[] = '';
            $lines[] = 'Clauses:';

            foreach ($this->sententialCEs as $idx => $sentCE) {
                $lines[] = sprintf(
                    '  %d: [%s] %s',
                    $idx,
                    $sentCE->sententialCE->value,
                    $sentCE->getText()
                );
            }
        }

        return implode("\n", $lines);
    }
}
