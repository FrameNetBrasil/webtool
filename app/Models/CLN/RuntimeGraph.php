<?php

namespace App\Models\CLN;

/**
 * The complete runtime graph containing all columns and pathways.
 */
class RuntimeGraph {
    /** @var array<string, FunctionalColumn> */
    private array $columns = [];

    /** @var Pathway[] */
    private array $pathways = [];

    // Fast lookup indices
    /** @var array<string, FunctionalColumn> */
    private array $literalIndex = [];    // word => column

    /** @var array<string, FunctionalColumn> */
    private array $posIndex = [];        // POS tag => column

    /** @var array<int, FunctionalColumn[]> */
    private array $levelIndex = [];      // level => columns at that level

    public string $output_dir;
    public function addColumn(FunctionalColumn $column): void {
        $this->columns[$column->id] = $column;

        // Update level index
        $level = $column->hierarchicalLevel;
        if (!isset($this->levelIndex[$level])) {
            $this->levelIndex[$level] = [];
        }
        $this->levelIndex[$level][] = $column;
    }

    public function addPathway(Pathway $pathway): void {
        $this->pathways[] = $pathway;
    }

    public function indexLiteral(string $word, FunctionalColumn $column): void {
        $this->literalIndex[strtolower($word)] = $column;
    }

    public function indexPOS(string $pos, FunctionalColumn $column): void {
        $this->posIndex[$pos] = $column;
    }

    public function getColumnById(string $id): ?FunctionalColumn {
        return $this->columns[$id] ?? null;
    }

    public function getColumnByLiteral(string $word): ?FunctionalColumn {
        return $this->literalIndex[strtolower($word)] ?? null;
    }

    public function getColumnByPOS(string $pos): ?FunctionalColumn {
        return $this->posIndex[$pos] ?? null;
    }

    public function getColumnsAtLevel(int $level): array {
        return $this->levelIndex[$level] ?? [];
    }

    public function getAllColumns(): array {
        return $this->columns;
    }

    public function getAllPathways(): array {
        return $this->pathways;
    }

    /**
     * Reset all activations for a new parse.
     */
    public function resetForNewParse(): void {
        foreach ($this->columns as $column) {
            $column->resetActivations();
            if ($column->som !== null) {
                $column->som->release();
            }
        }
    }
}

