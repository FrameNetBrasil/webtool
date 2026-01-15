<?php

namespace App\Repositories\Parser;

use App\Data\Parser\Construction\SearchData;
use App\Database\Criteria;

class ConstructionV4
{
    /**
     * Retrieve construction by ID
     */
    public static function byId(int $id): object
    {
        return Criteria::byId('parser_construction_v4', 'idConstruction', $id);
    }

    /**
     * List constructions by grammar graph ID with optional type filter
     */
    public static function listByGrammar(int $idGrammarGraph, ?string $constructionType = null): array
    {
        $query = Criteria::table('parser_construction_v4')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->orderBy('priority');

        if (!empty($constructionType)) {
            $query->where('constructionType', '=', $constructionType);
        }

        return $query->all();
    }

    /**
     * List constructions for grid display with search filters
     */
    public static function listToGrid(SearchData $search): array
    {
        $query = Criteria::table('parser_construction_v4')
            ->select(
                'idConstruction',
                'idGrammarGraph',
                'name',
                'constructionType',
                'priority',
                'enabled',
                'phrasalCE',
                'clausalCE',
                'sententialCE',
                'pattern',
                'created_at',
                'updated_at'
            )
            ->orderBy('priority');

        if (!empty($search->idGrammarGraph)) {
            $query->where('idGrammarGraph', '=', $search->idGrammarGraph);
        }

        if (!empty($search->name)) {
            $query->where('name', 'startswith', $search->name);
        }

        if (!empty($search->constructionType)) {
            $query->where('constructionType', '=', $search->constructionType);
        }

        if (isset($search->enabled)) {
            $query->where('enabled', '=', $search->enabled);
        }

        return $query->all();
    }

    /**
     * Create new construction
     */
    public static function create(array $data): int
    {
        return Criteria::create('parser_construction_v4', $data);
    }

    /**
     * Update construction
     */
    public static function update(int $id, array $data): void
    {
        Criteria::table('parser_construction_v4')
            ->where('idConstruction', '=', $id)
            ->update($data);
    }

    /**
     * Delete construction
     */
    public static function delete(int $id): void
    {
        Criteria::deleteById('parser_construction_v4', 'idConstruction', $id);
    }

    /**
     * Toggle enabled/disabled status
     */
    public static function toggle(int $id): void
    {
        $construction = self::byId($id);
        $newStatus = $construction->enabled ? 0 : 1;

        self::update($id, ['enabled' => $newStatus]);
    }

    /**
     * Get compiled pattern from construction object
     */
    public static function getCompiledPattern(object $construction): ?array
    {
        if (empty($construction->compiledPattern)) {
            return null;
        }

        return json_decode($construction->compiledPattern, true);
    }

    /**
     * Get constraints from construction object
     */
    public static function getConstraints(object $construction): array
    {
        if (empty($construction->constraints)) {
            return [];
        }

        return json_decode($construction->constraints, true) ?? [];
    }

    /**
     * Get examples from construction object
     */
    public static function getExamples(object $construction): array
    {
        if (empty($construction->examples)) {
            return [];
        }

        return json_decode($construction->examples, true) ?? [];
    }

    /**
     * Get invalidation patterns from construction object
     */
    public static function getInvalidationPatterns(object $construction): array
    {
        if (empty($construction->invalidationPatterns)) {
            return [];
        }

        return json_decode($construction->invalidationPatterns, true) ?? [];
    }

    /**
     * Get confirmation patterns from construction object
     */
    public static function getConfirmationPatterns(object $construction): array
    {
        if (empty($construction->confirmationPatterns)) {
            return [];
        }

        return json_decode($construction->confirmationPatterns, true) ?? [];
    }

    /**
     * Get semantics from construction object
     */
    public static function getSemantics(object $construction): array
    {
        if (empty($construction->semantics)) {
            return [];
        }

        return json_decode($construction->semantics, true) ?? [];
    }

    /**
     * List all constructions sharing the same phrasal CE label
     */
    public static function listSharingPhrasalCE(string $phrasalCE, int $idGrammarGraph): array
    {
        return Criteria::table('parser_construction_v4')
            ->where('phrasalCE', '=', $phrasalCE)
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->orderBy('priority')
            ->all();
    }

    /**
     * List all constructions sharing the same clausal CE label
     */
    public static function listSharingClausalCE(string $clausalCE, int $idGrammarGraph): array
    {
        return Criteria::table('parser_construction_v4')
            ->where('clausalCE', '=', $clausalCE)
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->orderBy('priority')
            ->all();
    }

    /**
     * List all constructions sharing the same sentential CE label
     */
    public static function listSharingSententialCE(string $sententialCE, int $idGrammarGraph): array
    {
        return Criteria::table('parser_construction_v4')
            ->where('sententialCE', '=', $sententialCE)
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->orderBy('priority')
            ->all();
    }

    /**
     * Check if construction name exists in grammar (for uniqueness validation)
     */
    public static function existsByName(int $idGrammarGraph, string $name, ?int $excludeId = null): bool
    {
        $query = Criteria::table('parser_construction_v4')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('name', '=', $name);

        if ($excludeId !== null) {
            $query->where('idConstruction', '!=', $excludeId);
        }

        return $query->first() !== null;
    }

    /**
     * Get maximum priority for a construction type within a grammar
     */
    public static function getMaxPriority(int $idGrammarGraph, string $constructionType): int
    {
        $result = Criteria::table('parser_construction_v4')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('constructionType', '=', $constructionType)
            ->selectRaw('MAX(priority) as maxPriority')
            ->first();

        return $result->maxPriority ?? 0;
    }

    /**
     * Get minimum priority for a construction type within a grammar
     */
    public static function getMinPriority(int $idGrammarGraph, string $constructionType): int
    {
        $result = Criteria::table('parser_construction_v4')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('constructionType', '=', $constructionType)
            ->selectRaw('MIN(priority) as minPriority')
            ->first();

        return $result->minPriority ?? 0;
    }

    /**
     * List all enabled constructions for a grammar graph
     */
    public static function listEnabled(int $idGrammarGraph): array
    {
        return Criteria::table('parser_construction_v4')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('enabled', '=', 1)
            ->orderBy('priority')
            ->all();
    }

    /**
     * Count constructions by type within a grammar
     */
    public static function countByType(int $idGrammarGraph, string $constructionType): int
    {
        return Criteria::table('parser_construction_v4')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->where('constructionType', '=', $constructionType)
            ->count();
    }

    /**
     * Get all unique CE labels (phrasal, clausal, sentential) used in a grammar
     */
    public static function getUniqueLabels(int $idGrammarGraph): object
    {
        $constructions = Criteria::table('parser_construction_v4')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->select('phrasalCE', 'clausalCE', 'sententialCE')
            ->all();

        $labels = (object) [
            'phrasal' => [],
            'clausal' => [],
            'sentential' => [],
        ];

        foreach ($constructions as $construction) {
            if (!empty($construction->phrasalCE) && !in_array($construction->phrasalCE, $labels->phrasal)) {
                $labels->phrasal[] = $construction->phrasalCE;
            }
            if (!empty($construction->clausalCE) && !in_array($construction->clausalCE, $labels->clausal)) {
                $labels->clausal[] = $construction->clausalCE;
            }
            if (!empty($construction->sententialCE) && !in_array($construction->sententialCE, $labels->sentential)) {
                $labels->sentential[] = $construction->sententialCE;
            }
        }

        sort($labels->phrasal);
        sort($labels->clausal);
        sort($labels->sentential);

        return $labels;
    }
}
