<?php

namespace App\Services\SeqGraph;

use Illuminate\Support\Facades\DB;

/**
 * Loads pattern graphs from the database.
 *
 * Retrieves compiled pattern graphs from parser_construction_v4 table
 * and prepares them for sequence graph building.
 */
class PatternGraphLoader
{
    /**
     * Load all pattern graphs from the database.
     *
     * @return array<string, array<string, mixed>> Pattern graphs indexed by construction name
     */
    public function loadAll(): array
    {
        $patterns = [];

        $constructions = DB::table('parser_construction_v4')
            ->select('name', 'compiledPattern')
            ->whereNotNull('compiledPattern')
            ->get();

        foreach ($constructions as $construction) {
            $patternGraph = json_decode($construction->compiledPattern, true);

            if ($patternGraph !== null && isset($patternGraph['nodes'], $patternGraph['edges'])) {
                $patterns[$construction->name] = $patternGraph;
            }
        }

        return $patterns;
    }

    /**
     * Load a specific pattern graph by construction name.
     *
     * @param  string  $name  Construction name
     * @return array<string, mixed>|null Pattern graph or null if not found
     */
    public function loadByName(string $name): ?array
    {
        $construction = DB::table('parser_construction_v4')
            ->select('compiledPattern')
            ->where('name', $name)
            ->first();

        if ($construction === null || $construction->compiledPattern === null) {
            return null;
        }

        $patternGraph = json_decode($construction->compiledPattern, true);

        if ($patternGraph !== null && isset($patternGraph['nodes'], $patternGraph['edges'])) {
            return $patternGraph;
        }

        return null;
    }

    /**
     * Load multiple pattern graphs by construction names.
     *
     * @param  array<string>  $names  Construction names
     * @return array<string, array<string, mixed>> Pattern graphs indexed by construction name
     */
    public function loadByNames(array $names): array
    {
        $patterns = [];

        $constructions = DB::table('parser_construction_v4')
            ->select('name', 'compiledPattern')
            ->whereIn('name', $names)
            ->whereNotNull('compiledPattern')
            ->get();

        foreach ($constructions as $construction) {
            $patternGraph = json_decode($construction->compiledPattern, true);

            if ($patternGraph !== null && isset($patternGraph['nodes'], $patternGraph['edges'])) {
                $patterns[$construction->name] = $patternGraph;
            }
        }

        return $patterns;
    }
}
