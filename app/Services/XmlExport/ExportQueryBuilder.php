<?php

namespace App\Services\XmlExport;


/**
 * Export filter and query builder
 */
class ExportQueryBuilder
{
    private array $filters = [];
    private array $joins = [];
    private array $orderBy = [];
    private ?int $limit = null;
    private int $offset = 0;

    /**
     * Add filter condition
     */
    public function addFilter(string $field, string $operator, $value): self
    {
        $this->filters[] = [$field, $operator, $value];
        return $this;
    }

    /**
     * Add join clause
     */
    public function addJoin(string $table, string $condition, string $type = 'inner'): self
    {
        $this->joins[] = [$table, $condition, $type];
        return $this;
    }

    /**
     * Add order by clause
     */
    public function addOrderBy(string $field, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [$field, $direction];
        return $this;
    }

    /**
     * Set limit and offset
     */
    public function setLimit(int $limit, int $offset = 0): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    /**
     * Build query for specific table
     */
    public function buildQuery(string $baseTable): \App\Database\Criteria
    {
        $query = \App\Database\Criteria::table($baseTable);

        // Apply joins
        foreach ($this->joins as [$table, $condition, $type]) {
            if ($type === 'left') {
                $query->leftJoin($table, function($join) use ($condition) {
                    $join->on($condition);
                });
            } else {
                $query->join($table, function($join) use ($condition) {
                    $join->on($condition);
                });
            }
        }

        // Apply filters
        foreach ($this->filters as [$field, $operator, $value]) {
            $query->where($field, $operator, $value);
        }

        // Apply ordering
        foreach ($this->orderBy as [$field, $direction]) {
            $query->orderBy($field, $direction);
        }

        // Apply limit and offset
        if ($this->limit !== null) {
            $query->limit($this->limit);
            if ($this->offset > 0) {
                $query->offset($this->offset);
            }
        }

        return $query;
    }

    /**
     * Reset all filters and conditions
     */
    public function reset(): self
    {
        $this->filters = [];
        $this->joins = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = 0;
        return $this;
    }
}
