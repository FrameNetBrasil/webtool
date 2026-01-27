<?php

namespace App\Models\SeqGraph;

/**
 * Represents a directed edge in a sequence graph.
 *
 * Edges connect nodes in the graph and can be marked as bypass edges.
 * Bypass edges allow activation to skip optional elements while maintaining
 * sequential flow through the graph.
 */
class SeqEdge
{
    /**
     * The ID of the source node.
     */
    public string $from;

    /**
     * The ID of the target node.
     */
    public string $to;

    /**
     * Whether this edge bypasses an optional element.
     *
     * Bypass edges allow activation propagation to skip optional nodes,
     * enabling patterns like "the [big] cat" where "big" is optional.
     */
    public bool $bypass;

    /**
     * Create a new sequence edge.
     *
     * @param  string  $from  The source node ID
     * @param  string  $to  The target node ID
     * @param  bool  $bypass  Whether this is a bypass edge (default: false)
     */
    public function __construct(string $from, string $to, bool $bypass = false)
    {
        $this->from = $from;
        $this->to = $to;
        $this->bypass = $bypass;
    }
}
