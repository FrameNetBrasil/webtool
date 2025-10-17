<?php

namespace App\Services\Neo4j;

class QueryBuilderService
{
    private string $query = '';

    private array $parameters = [];

    private array $parts = [];

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): self
    {
        $this->query = '';
        $this->parameters = [];
        $this->parts = [
            'match' => [],
            'create' => [],
            'merge' => [],
            'where' => [],
            'with' => [],
            'return' => [],
            'set' => [],
            'delete' => [],
            'order' => [],
            'skip' => null,
            'limit' => null,
        ];

        return $this;
    }

    public function match(string $pattern): self
    {
        $this->parts['match'][] = $pattern;

        return $this;
    }

    public function create(string $pattern): self
    {
        $this->parts['create'][] = $pattern;

        return $this;
    }

    public function merge(string $pattern): self
    {
        $this->parts['merge'][] = $pattern;

        return $this;
    }

    public function where(string $condition): self
    {
        $this->parts['where'][] = $condition;

        return $this;
    }

    public function whereParameter(string $field, string $operator, mixed $value): self
    {
        $uOp = strtoupper($operator);
        $paramKey = $this->addParameter($value);

        if ($uOp === 'STARTSWITH') {
            $this->parts['where'][] = "{$field} STARTS WITH \${$paramKey}";
        } elseif ($uOp === 'CONTAINS') {
            $this->parts['where'][] = "{$field} CONTAINS \${$paramKey}";
        } elseif ($uOp === 'IN') {
            $this->parts['where'][] = "{$field} IN \${$paramKey}";
        } elseif ($uOp === 'NOT IN') {
            $this->parts['where'][] = "NOT {$field} IN \${$paramKey}";
        } elseif (strtoupper((string) $value) === 'NULL' || is_null($value)) {
            $this->parts['where'][] = "{$field} IS NULL";
        } elseif (strtoupper((string) $value) === 'NOT NULL') {
            $this->parts['where'][] = "{$field} IS NOT NULL";
        } else {
            $this->parts['where'][] = "{$field} {$operator} \${$paramKey}";
        }

        return $this;
    }

    public function with(string $expression): self
    {
        $this->parts['with'][] = $expression;

        return $this;
    }

    public function returnClause(string $expression): self
    {
        $this->parts['return'][] = $expression;

        return $this;
    }

    public function set(string $expression): self
    {
        $this->parts['set'][] = $expression;

        return $this;
    }

    public function delete(string $expression): self
    {
        $this->parts['delete'][] = $expression;

        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->parts['order'][] = "{$field} {$direction}";

        return $this;
    }

    public function skip(int $count): self
    {
        $this->parts['skip'] = $count;

        return $this;
    }

    public function limit(int $count): self
    {
        $this->parts['limit'] = $count;

        return $this;
    }

    public function addParameter(mixed $value): string
    {
        $key = 'param_'.count($this->parameters);
        $this->parameters[$key] = $value;

        return $key;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function build(): string
    {
        $query = [];

        if (!empty($this->parts['create'])) {
            $query[] = 'CREATE '.implode(', ', $this->parts['create']);
        }

        if (!empty($this->parts['merge'])) {
            $query[] = 'MERGE '.implode(', ', $this->parts['merge']);
        }

        if (!empty($this->parts['match'])) {
            $query[] = 'MATCH '.implode(', ', $this->parts['match']);
        }

        if (!empty($this->parts['where'])) {
            $query[] = 'WHERE '.implode(' AND ', $this->parts['where']);
        }

        if (!empty($this->parts['set'])) {
            $query[] = 'SET '.implode(', ', $this->parts['set']);
        }

        if (!empty($this->parts['delete'])) {
            $query[] = 'DELETE '.implode(', ', $this->parts['delete']);
        }

        if (!empty($this->parts['with'])) {
            $query[] = 'WITH '.implode(', ', $this->parts['with']);
        }

        if (!empty($this->parts['return'])) {
            $query[] = 'RETURN '.implode(', ', $this->parts['return']);
        }

        if (!empty($this->parts['order'])) {
            $query[] = 'ORDER BY '.implode(', ', $this->parts['order']);
        }

        if ($this->parts['skip'] !== null) {
            $query[] = 'SKIP '.$this->parts['skip'];
        }

        if ($this->parts['limit'] !== null) {
            $query[] = 'LIMIT '.$this->parts['limit'];
        }

        return implode(' ', $query);
    }

    public function __toString(): string
    {
        return $this->build();
    }

    public static function nodePattern(string $variable, string $label = '', array $properties = []): string
    {
        $pattern = "({$variable}";

        if ($label) {
            $pattern .= ":{$label}";
        }

        if (!empty($properties)) {
            $props = [];
            foreach ($properties as $key => $value) {
                if (is_string($value)) {
                    $props[] = "{$key}: '{$value}'";
                } else {
                    $props[] = "{$key}: {$value}";
                }
            }
            $pattern .= ' {'.implode(', ', $props).'}';
        }

        $pattern .= ')';

        return $pattern;
    }

    public static function relationshipPattern(string $type = '', string $variable = '', array $properties = []): string
    {
        $pattern = '[';

        if ($variable) {
            $pattern .= $variable;
        }

        if ($type) {
            $pattern .= ":{$type}";
        }

        if (!empty($properties)) {
            $props = [];
            foreach ($properties as $key => $value) {
                if (is_string($value)) {
                    $props[] = "{$key}: '{$value}'";
                } else {
                    $props[] = "{$key}: {$value}";
                }
            }
            $pattern .= ' {'.implode(', ', $props).'}';
        }

        $pattern .= ']';

        return $pattern;
    }

    public static function escapeProperty(string $property): string
    {
        return '`'.str_replace('`', '``', $property).'`';
    }
}