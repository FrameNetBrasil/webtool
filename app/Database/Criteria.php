<?php

namespace App\Database;

use App\Services\AppService;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class Criteria extends Builder
{
    public static string $database = '';

    public function __construct()
    {
        if (self::$database != '') {
            $connection = DB::connection(self::$database);
        } else {
            $connection = app()->make(ConnectionInterface::class);
        }
        parent::__construct($connection);
    }

    public static function table(string $tableName): static
    {
        $self = new self();
        $self->from($tableName);
        return $self;
    }

    public static function byFilter(string $tableName, array $filter): static
    {
        $self = new self();
        return $self->from($tableName)
            ->filter($filter);
    }

    public static function byFilterLanguage(string $tableName, array $filter, ?string $languageColumn = null, ?int $idLanguage = null): static
    {
        $languageColumn ??= 'idLanguage';
        $idLanguage ??= AppService::getCurrentIdLanguage();
        $self = new self();
        return $self->from($tableName)
            ->filter($filter)
            ->where($languageColumn, '=', $idLanguage);
    }

    public static function byId(string $tableName, string $key, mixed $value): null|object
    {
        $self = new self();
        return $self->from($tableName)
            ->where($key, $value)
            ->first();
    }

    public static function deleteById(string $tableName, string $key, mixed $value): void
    {
        $self = new self();
        $self->from($tableName)
            ->where($key, $value)
            ->delete();
    }

    public static function one(string $tableName, array $filter): null|object
    {
        $self = new self();
        return $self->from($tableName)
            ->filter($filter)
            ->first();
    }

    public static function create(string $tableName, array $values): ?int
    {
        $self = new self();
        $self->from($tableName)
            ->insert($values);
        return $self->getConnection()->getPdo()->lastInsertId();
    }

    public static function call(string $routine, array $params): mixed
    {
        return DB::select("call {$routine}", $params);
    }

    public static function function (string $routine, array $params): mixed
    {
        $result = DB::select("select {$routine} as result", $params);
        return $result[0]->result;
    }

    public static function var(string $var): mixed
    {
        $result = DB::select("select {$var}");
        return $result[0]->{$var};
    }

    public function filter(array $filter): Criteria
    {
        if (!empty($filter)) {
            $filter = is_string($filter[0]) ? [$filter] : $filter;
            foreach ($filter as [$field, $op, $value]) {
                if (!is_null($value)) {
                    $this->where($field, $op, $value);
                }
            }
        }
        return $this;
    }

    /**
     * @param string|null $className
     * @return array array<$className>
     */
    public function all(): array
    {
        return $this->get()->all();
    }

    public function chunkResult(string $fieldKey = '', string $fieldValue = ''): array
    {
        return $this->get()->pluck($fieldValue, $fieldKey)->all();
    }

    public function treeResult(string $groupBy): Collection
    {
        return $this->get()
            ->groupBy($groupBy);
    }

    public function keyBy(string $field): Collection
    {
        return $this->get()
            ->keyBy($field);
    }

    public function where($column, $operator = null, $value = null, $boolean = 'and'): static
    {
        if (func_num_args() > 2) {
            $uOp = strtoupper($operator ?? "");
            if ($uOp == 'STARTSWITH') {
                $operator = 'LIKE';
                $value = $value . '%';
            } elseif ($uOp == 'CONTAINS') {
                $operator = 'LIKE';
                $value = '%' . $value . '%';
            }
            $uValue = is_string($value) ? strtoupper($value) : $value;
            if (($uValue === 'NULL') || is_null($value)) {
                $this->whereNull($column);
            } else if ($uValue === 'NOT NULL') {
                $this->whereNotNull($column);
            } else if ($uOp === 'IN') {
                $this->whereIn($column, $value);
            } else if ($uOp === 'NOT IN') {
                $this->whereNotIn($column, $value);
            } else if ($uOp === 'LEFT') {
                $this->whereRaw("(({$column} = {$value}) or ({$column} IS NULL))");
            } else {
                parent::where($column, $operator, $value, $boolean);
            }
        } else {
            parent::where($column, $operator, $value, $boolean);
        }

        return $this;
    }
}
