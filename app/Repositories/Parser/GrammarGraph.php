<?php

namespace App\Repositories\Parser;

use App\Data\Parser\Grammar\SearchData;
use App\Database\Criteria;

class GrammarGraph
{
    /**
     * Retrieve grammar graph by ID
     */
    public static function byId(int $id): object
    {
        return Criteria::byId('parser_grammar_graph', 'idGrammarGraph', $id);
    }

    /**
     * List all grammar graphs
     */
    public static function list(): array
    {
        return Criteria::table('parser_grammar_graph')
            ->select('idGrammarGraph', 'name', 'language', 'description')
            ->orderBy('name')
            ->all();
    }

    /**
     * List grammar graphs by language
     */
    public static function listByLanguage(string $language): array
    {
        return Criteria::table('parser_grammar_graph')
            ->select('idGrammarGraph', 'name', 'language', 'description')
            ->where('language', '=', $language)
            ->orderBy('name')
            ->all();
    }

    /**
     * Get grammar graph with V4 constructions
     */
    public static function getWithStructure(int $id): object
    {
        $grammar = self::byId($id);
        $grammar->constructions = ConstructionV4::listByGrammar($id);

        return $grammar;
    }

    /**
     * Create new grammar graph
     */
    public static function create(array $data): int
    {
        return Criteria::create('parser_grammar_graph', $data);
    }

    /**
     * Update grammar graph
     */
    public static function update(int $id, array $data): void
    {
        Criteria::table('parser_grammar_graph')
            ->where('idGrammarGraph', '=', $id)
            ->update($data);
    }

    /**
     * Delete grammar graph
     */
    public static function delete(int $id): void
    {
        Criteria::deleteById('parser_grammar_graph', 'idGrammarGraph', $id);
    }

    /**
     * List grammar graphs for grid display with search filters (V4)
     */
    public static function listToGrid(SearchData $search): array
    {
        $query = Criteria::table('parser_grammar_graph')
            ->select('idGrammarGraph', 'name', 'language', 'description', 'created_at')
            ->orderBy('name');

        if (! empty($search->name)) {
            $query->where('name', 'startswith', $search->name);
        }

        if (! empty($search->language)) {
            $query->where('language', '=', $search->language);
        }

        $grammars = $query->all();

        // Add construction count for each grammar
        foreach ($grammars as $grammar) {
            $grammar->constructionCount = self::countConstructions($grammar->idGrammarGraph);
        }

        return $grammars;
    }

    /**
     * Count V4 constructions for a grammar graph
     */
    public static function countConstructions(int $idGrammarGraph): int
    {
        return Criteria::table('parser_construction_v4')
            ->where('idGrammarGraph', '=', $idGrammarGraph)
            ->count();
    }
}
