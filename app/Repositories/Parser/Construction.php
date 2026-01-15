<?php

namespace App\Repositories\Parser;

use App\Database\Criteria;

class Construction
{
    /**
     * Retrieve construction by ID
     */
    public static function byId(int $id): object
    {
        return Criteria::byId('parser_constructions', 'idConstruction', $id);
    }

    /**
     * List all constructions for a grammar graph
     */
    public static function listByGrammar(int $idGrammarGraph): array
    {
        return Criteria::table('parser_constructions')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->all();
    }

    /**
     * Get enabled constructions for a grammar graph
     * Ordered by priority (descending) for matching
     */
    public static function getEnabled(int $idGrammarGraph): array
    {
        return Criteria::table('parser_constructions')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('enabled', '=', true)
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->all();
    }

    /**
     * Get construction by name
     */
    public static function getByName(int $idGrammarGraph, string $name): ?object
    {
        $result = Criteria::table('parser_constructions')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('name', '=', $name)
            ->first();

        return $result ?: null;
    }

    /**
     * Get constructions by semantic type
     */
    public static function listBySemanticType(int $idGrammarGraph, string $semanticType): array
    {
        return Criteria::table('parser_constructions')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('semanticType', '=', $semanticType)
            ->orderBy('priority', 'desc')
            ->orderBy('name')
            ->all();
    }

    /**
     * Create new construction
     *
     * @param  array  $data  Construction data including compiledGraph
     * @return int Construction ID
     */
    public static function create(array $data): int
    {
        // Ensure JSON fields are encoded
        if (isset($data['compiledGraph']) && is_array($data['compiledGraph'])) {
            $data['compiledGraph'] = json_encode($data['compiledGraph']);
        }

        if (isset($data['semantics']) && is_array($data['semantics'])) {
            $data['semantics'] = json_encode($data['semantics']);
        }

        return Criteria::create('parser_constructions', $data);
    }

    /**
     * Update construction
     */
    public static function update(int $id, array $data): void
    {
        // Ensure JSON fields are encoded
        if (isset($data['compiledGraph']) && is_array($data['compiledGraph'])) {
            $data['compiledGraph'] = json_encode($data['compiledGraph']);
        }

        if (isset($data['semantics']) && is_array($data['semantics'])) {
            $data['semantics'] = json_encode($data['semantics']);
        }

        Criteria::table('parser_constructions')
            ->where('idConstruction', '=', $id)
            ->update($data);
    }

    /**
     * Delete construction
     */
    public static function delete(int $id): void
    {
        Criteria::deleteById('parser_constructions', 'idConstruction', $id);
    }

    /**
     * Get compiled graph from construction
     *
     * @return array Decoded graph structure
     */
    public static function getCompiledGraph(object $construction): array
    {
        if (is_string($construction->compiledGraph)) {
            return json_decode($construction->compiledGraph, true);
        }

        return $construction->compiledGraph;
    }

    /**
     * Get semantics from construction
     *
     * @return array|null Decoded semantics or null
     */
    public static function getSemantics(object $construction): ?array
    {
        if (empty($construction->semantics)) {
            return null;
        }

        if (is_string($construction->semantics)) {
            return json_decode($construction->semantics, true);
        }

        return $construction->semantics;
    }

    /**
     * Enable construction
     */
    public static function enable(int $id): void
    {
        self::update($id, ['enabled' => true]);
    }

    /**
     * Disable construction
     */
    public static function disable(int $id): void
    {
        self::update($id, ['enabled' => false]);
    }

    /**
     * Update priority
     */
    public static function setPriority(int $id, int $priority): void
    {
        self::update($id, ['priority' => $priority]);
    }

    /**
     * Check if construction exists by name
     */
    public static function exists(int $idGrammarGraph, string $name): bool
    {
        return self::getByName($idGrammarGraph, $name) !== null;
    }

    /**
     * Count constructions for a grammar
     */
    public static function count(int $idGrammarGraph): int
    {
        return Criteria::table('parser_constructions')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->count();
    }

    /**
     * Count enabled constructions for a grammar
     */
    public static function countEnabled(int $idGrammarGraph): int
    {
        return Criteria::table('parser_constructions')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('enabled', '=', true)
            ->count();
    }

    /**
     * Get all construction names for a grammar
     */
    public static function listNames(int $idGrammarGraph): array
    {
        $results = Criteria::table('parser_constructions')
            ->select(['name'])
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->orderBy('name')
            ->all();

        return array_map(fn ($r) => $r->name, $results);
    }
}
