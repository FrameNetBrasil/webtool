<?php

namespace App\Services\Parser\V4;

use App\Data\Parser\V4\Construction\CreateData;
use App\Data\Parser\V4\Construction\UpdateData;
use App\Repositories\Parser\ConstructionV4;
use App\Repositories\Parser\GrammarGraph;
use App\Services\Parser\PatternCompiler;
use Exception;

/**
 * Construction Service V4
 *
 * Handles business logic for construction management including:
 * - Pattern compilation and validation
 * - Construction CRUD with automatic pattern compilation
 * - Pattern testing against sentences
 * - Import/Export functionality
 */
class ConstructionServiceV4
{
    private PatternCompiler $compiler;

    public function __construct()
    {
        $this->compiler = new PatternCompiler;
    }

    /**
     * Create construction with automatic pattern compilation
     */
    public function compileAndStoreV4(CreateData $data): int
    {
        // Validate and compile pattern
        $validation = $this->validatePattern($data->pattern);

        if (!$validation['valid']) {
            throw new Exception('Pattern validation failed: ' . $validation['error']);
        }

        // Set default priority based on construction type if not specified
        if ($data->priority === 50) {
            $data->priority = $this->getDefaultPriority($data->constructionType);
        }

        // Validate priority is within correct range for type
        $this->validatePriorityRange($data->constructionType, $data->priority);

        // Prepare data for database
        $dbData = [
            'idGrammarGraph' => $data->idGrammarGraph,
            'name' => $data->name,
            'constructionType' => $data->constructionType,
            'pattern' => $data->pattern,
            'compiledPattern' => json_encode($validation['compiled']),
            'priority' => $data->priority,
            'enabled' => $data->enabled ? 1 : 0,
            'phrasalCE' => $data->phrasalCE,
            'clausalCE' => $data->clausalCE,
            'sententialCE' => $data->sententialCE,
            'constraints' => $data->constraints,
            'aggregateAs' => $data->aggregateAs,
            'semanticType' => $data->semanticType,
            'semantics' => $data->semantics,
            'lookaheadEnabled' => $data->lookaheadEnabled ? 1 : 0,
            'lookaheadMaxDistance' => $data->lookaheadMaxDistance,
            'invalidationPatterns' => $data->invalidationPatterns,
            'confirmationPatterns' => $data->confirmationPatterns,
            'description' => $data->description,
            'examples' => $data->examples,
        ];

        return ConstructionV4::create($dbData);
    }

    /**
     * Update construction with recompilation
     */
    public function updateV4(int $idConstruction, UpdateData $data): void
    {
        // Validate and compile pattern
        $validation = $this->validatePattern($data->pattern);

        if (!$validation['valid']) {
            throw new Exception('Pattern validation failed: ' . $validation['error']);
        }

        // Validate priority is within correct range for type
        $this->validatePriorityRange($data->constructionType, $data->priority);

        // Prepare data for database
        $dbData = [
            'idGrammarGraph' => $data->idGrammarGraph,
            'name' => $data->name,
            'constructionType' => $data->constructionType,
            'pattern' => $data->pattern,
            'compiledPattern' => json_encode($validation['compiled']),
            'priority' => $data->priority,
            'enabled' => $data->enabled ? 1 : 0,
            'phrasalCE' => $data->phrasalCE,
            'clausalCE' => $data->clausalCE,
            'sententialCE' => $data->sententialCE,
            'constraints' => $data->constraints,
            'aggregateAs' => $data->aggregateAs,
            'semanticType' => $data->semanticType,
            'semantics' => $data->semantics,
            'lookaheadEnabled' => $data->lookaheadEnabled ? 1 : 0,
            'lookaheadMaxDistance' => $data->lookaheadMaxDistance,
            'invalidationPatterns' => $data->invalidationPatterns,
            'confirmationPatterns' => $data->confirmationPatterns,
            'description' => $data->description,
            'examples' => $data->examples,
        ];

        ConstructionV4::update($idConstruction, $dbData);
    }

    /**
     * Validate pattern and return compilation result
     */
    public function validatePattern(string $pattern): array
    {
        if (empty(trim($pattern))) {
            return [
                'valid' => false,
                'error' => 'Pattern cannot be empty',
                'compiled' => null,
            ];
        }

        try {
            $compiled = $this->compiler->compile($pattern);

            return [
                'valid' => true,
                'error' => null,
                'compiled' => $compiled,
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'compiled' => null,
            ];
        }
    }

    /**
     * Test pattern against sentence
     */
    public function testPatternAgainstSentence(string $pattern, string $sentence, int $idGrammarGraph): array
    {
        // Validate pattern first
        $validation = $this->validatePattern($pattern);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error'],
                'matches' => [],
            ];
        }

        try {
            // Here we would integrate with the actual parser engine
            // For now, returning a placeholder structure
            return [
                'success' => true,
                'error' => null,
                'sentence' => $sentence,
                'pattern' => $pattern,
                'compiled' => $validation['compiled'],
                'matches' => [],
                'message' => 'Pattern compiled successfully. Parser integration pending.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'matches' => [],
            ];
        }
    }

    /**
     * Export constructions to JSON
     */
    public function exportToJson(int $idGrammarGraph): string
    {
        $grammar = GrammarGraph::byId($idGrammarGraph);
        $constructions = ConstructionV4::listByGrammar($idGrammarGraph);

        $export = [
            'version' => '4.0',
            'grammar' => [
                'idGrammarGraph' => $grammar->idGrammarGraph,
                'name' => $grammar->name,
                'language' => $grammar->language,
                'description' => $grammar->description ?? '',
            ],
            'constructions' => [],
        ];

        foreach ($constructions as $construction) {
            $export['constructions'][] = [
                'name' => $construction->name,
                'constructionType' => $construction->constructionType,
                'pattern' => $construction->pattern,
                'priority' => $construction->priority,
                'enabled' => (bool) $construction->enabled,
                'phrasalCE' => $construction->phrasalCE,
                'clausalCE' => $construction->clausalCE,
                'sententialCE' => $construction->sententialCE,
                'constraints' => $construction->constraints,
                'aggregateAs' => $construction->aggregateAs,
                'semanticType' => $construction->semanticType,
                'semantics' => $construction->semantics,
                'lookaheadEnabled' => (bool) $construction->lookaheadEnabled,
                'lookaheadMaxDistance' => $construction->lookaheadMaxDistance,
                'invalidationPatterns' => $construction->invalidationPatterns,
                'confirmationPatterns' => $construction->confirmationPatterns,
                'description' => $construction->description,
                'examples' => $construction->examples,
            ];
        }

        return json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Import constructions from JSON
     */
    public function importFromJson(int $idGrammarGraph, string $jsonFile, bool $overwrite): array
    {
        $data = json_decode($jsonFile, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }

        if (!isset($data['version']) || $data['version'] !== '4.0') {
            throw new Exception('Invalid or unsupported version. Expected version 4.0');
        }

        if (!isset($data['constructions']) || !is_array($data['constructions'])) {
            throw new Exception('Invalid format: constructions array not found');
        }

        $report = [
            'total' => count($data['constructions']),
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($data['constructions'] as $index => $constructionData) {
            try {
                $name = $constructionData['name'] ?? "construction_$index";

                // Check if exists
                $exists = ConstructionV4::existsByName($idGrammarGraph, $name);

                if ($exists && !$overwrite) {
                    $report['skipped']++;
                    continue;
                }

                // Create CreateData from import
                $createData = new CreateData(
                    idGrammarGraph: $idGrammarGraph,
                    name: $name,
                    constructionType: $constructionData['constructionType'] ?? 'phrasal',
                    pattern: $constructionData['pattern'] ?? '',
                    priority: $constructionData['priority'] ?? 50,
                    enabled: $constructionData['enabled'] ?? true,
                    phrasalCE: $constructionData['phrasalCE'] ?? null,
                    clausalCE: $constructionData['clausalCE'] ?? null,
                    sententialCE: $constructionData['sententialCE'] ?? null,
                    constraints: $constructionData['constraints'] ?? null,
                    aggregateAs: $constructionData['aggregateAs'] ?? null,
                    semanticType: $constructionData['semanticType'] ?? null,
                    semantics: $constructionData['semantics'] ?? null,
                    lookaheadEnabled: $constructionData['lookaheadEnabled'] ?? false,
                    lookaheadMaxDistance: $constructionData['lookaheadMaxDistance'] ?? 3,
                    invalidationPatterns: $constructionData['invalidationPatterns'] ?? null,
                    confirmationPatterns: $constructionData['confirmationPatterns'] ?? null,
                    description: $constructionData['description'] ?? null,
                    examples: $constructionData['examples'] ?? null,
                );

                if ($exists && $overwrite) {
                    // Find and update existing
                    $existing = ConstructionV4::listByGrammar($idGrammarGraph);
                    $existingConstruction = collect($existing)->firstWhere('name', $name);
                    if ($existingConstruction) {
                        $updateData = new UpdateData(
                            idConstruction: $existingConstruction->idConstruction,
                            ...(array) $createData
                        );
                        $this->updateV4($existingConstruction->idConstruction, $updateData);
                    }
                } else {
                    $this->compileAndStoreV4($createData);
                }

                $report['imported']++;
            } catch (Exception $e) {
                $report['errors'][] = "Construction $index ({$name}): " . $e->getMessage();
            }
        }

        return $report;
    }

    /**
     * Get default priority for construction type
     */
    private function getDefaultPriority(string $constructionType): int
    {
        return match ($constructionType) {
            'sentential' => 10,
            'clausal' => 35,
            'phrasal' => 75,
            'mwe' => 150,
            default => 50,
        };
    }

    /**
     * Validate priority is within valid range for construction type
     */
    private function validatePriorityRange(string $constructionType, int $priority): void
    {
        $ranges = [
            'sentential' => [1, 19],
            'clausal' => [20, 49],
            'phrasal' => [50, 99],
            'mwe' => [100, 199],
        ];

        if (!isset($ranges[$constructionType])) {
            throw new Exception("Invalid construction type: $constructionType");
        }

        [$min, $max] = $ranges[$constructionType];

        if ($priority < $min || $priority > $max) {
            throw new Exception("Priority $priority is out of range for $constructionType constructions (valid range: $min-$max)");
        }
    }
}
