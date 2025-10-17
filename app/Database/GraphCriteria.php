<?php

namespace App\Database;

use App\Services\AppService;
use App\Services\Neo4j\ConnectionService;
use App\Services\Neo4j\QueryBuilderService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Result;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\Node;
use Laudis\Neo4j\Types\Relationship;
use RuntimeException;

class GraphCriteria
{
    private ClientInterface $client;

    private QueryBuilderService $queryBuilder;

    private string $connectionName;

    private bool $logQueries;

    public static string $connection = '';

    public function __construct(?string $connectionName = null)
    {
        if (!ConnectionService::isEnabled()) {
            throw new RuntimeException('Neo4j is not enabled. Set NEO4J_ENABLED=true in your environment.');
        }

        $this->connectionName = $connectionName ?? 'default';
        $this->client = ConnectionService::connection($this->connectionName);
        $this->queryBuilder = new QueryBuilderService;
        $this->logQueries = config('webtool.neo4j.logging.enabled', false);
    }

    public static function node(string $label, string $variable = 'n'): static
    {
        $self = new self(self::$connection ?: null);
        $nodePattern = QueryBuilderService::nodePattern($variable, $label);
        $self->queryBuilder->match($nodePattern);

        return $self;
    }

    public static function match(string $pattern): static
    {
        $self = new self(self::$connection ?: null);
        $self->queryBuilder->match($pattern);

        return $self;
    }

    public static function createNode(string $label, array $properties = [], string $variable = 'n'): ?object
    {
        $self = new self(self::$connection ?: null);

        $propertyParams = [];
        foreach ($properties as $key => $value) {
            $paramKey = $self->queryBuilder->addParameter($value);
            $propertyParams[] = "{$key}: \${$paramKey}";
        }

        if (!isset($properties['created_at'])) {
            $timestampParam = $self->queryBuilder->addParameter(now()->toISOString());
            $propertyParams[] = "created_at: \${$timestampParam}";
        }

        $propertiesString = !empty($propertyParams) ? ' {'.implode(', ', $propertyParams).'}' : '';
        $createPattern = "({$variable}:{$label}{$propertiesString})";

        $self->queryBuilder->create($createPattern)
            ->returnClause($variable);

        $result = $self->execute();
        $record = $result->first();
        if ($record) {
            $node = $record->get($variable);
            return $self->processValue($node);
        }

        return null;
    }

    public static function createRelation(mixed $fromNodeId, mixed $toNodeId, string $relationType, array $properties = []): ?object
    {
        $self = new self(self::$connection ?: null);

        $fromParam = $self->queryBuilder->addParameter($fromNodeId);
        $toParam = $self->queryBuilder->addParameter($toNodeId);

        $propertyParams = [];
        foreach ($properties as $key => $value) {
            $paramKey = $self->queryBuilder->addParameter($value);
            $propertyParams[] = "{$key}: \${$paramKey}";
        }

        $timestampParam = $self->queryBuilder->addParameter(now()->toISOString());
        $propertyParams[] = "created_at: \${$timestampParam}";

        $propertiesString = !empty($propertyParams) ? ' {'.implode(', ', $propertyParams).'}' : '';

        $query = "MATCH (from), (to) WHERE ID(from) = \${$fromParam} AND ID(to) = \${$toParam} CREATE (from)-[r:{$relationType}{$propertiesString}]->(to) RETURN r";

        $result = $self->client->run($query, $self->queryBuilder->getParameters());
        $record = $result->first();
        if ($record) {
            $relationship = $record->get('r');
            return $self->processValue($relationship);
        }

        return null;
    }

    public static function byFilter(string $label, array $filter, string $variable = 'n'): static
    {
        $self = new self(self::$connection ?: null);
        $nodePattern = QueryBuilderService::nodePattern($variable, $label);
        $self->queryBuilder->match($nodePattern);
        return $self->filter($filter);
    }

    public static function byFilterLanguage(string $label, array $filter, ?string $languageProperty = null, ?int $idLanguage = null, string $variable = 'n'): static
    {
        $languageProperty ??= 'idLanguage';
        $idLanguage ??= AppService::getCurrentIdLanguage();
        $self = new self(self::$connection ?: null);
        $nodePattern = QueryBuilderService::nodePattern($variable, $label);
        $self->queryBuilder->match($nodePattern)
            ->whereParameter("{$variable}.{$languageProperty}", '=', $idLanguage);
        return $self->filter($filter);
    }

    public static function byId(string $label, string $key, mixed $value, string $variable = 'n'): ?object
    {
        $self = new self(self::$connection ?: null);
        $nodePattern = QueryBuilderService::nodePattern($variable, $label);
        $self->queryBuilder->match($nodePattern)
            ->whereParameter("{$variable}.{$key}", '=', $value)
            ->returnClause($variable);

        $result = $self->execute();
        $record = $result->first();
        if ($record) {
            return $self->processValue($record->get($variable));
        }
        return null;
    }

    public static function one(string $label, array $filter, string $variable = 'n'): ?object
    {
        $self = new self(self::$connection ?: null);
        $nodePattern = QueryBuilderService::nodePattern($variable, $label);
        $self->queryBuilder->match($nodePattern);
        $self->filter($filter);
        return $self->first();
    }

    public static function deleteByFilter(string $label, array $filter, string $variable = 'n'): int
    {
        $self = new self(self::$connection ?: null);
        $nodePattern = QueryBuilderService::nodePattern($variable, $label);
        $self->queryBuilder->match($nodePattern);
        $self->filter($filter);
        return $self->delete();
    }

    public function filter(array $filter): GraphCriteria
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

    public function where(string $field, string $operator = '=', mixed $value = null): self
    {
        if (func_num_args() > 2) {
            $this->queryBuilder->whereParameter($field, $operator, $value);
        } else {
            $this->queryBuilder->where($field);
        }

        return $this;
    }

    public function withRelations(string $relationType, string $direction = 'outgoing', string $targetLabel = '', string $relVariable = 'r', string $targetVariable = 'm'): self
    {
        $relationPattern = QueryBuilderService::relationshipPattern($relationType, $relVariable);

        switch ($direction) {
            case 'incoming':
                $pattern = "({$targetVariable}:{$targetLabel})-{$relationPattern}->(n)";
                break;
            case 'bidirectional':
                $pattern = "({$targetVariable}:{$targetLabel})-{$relationPattern}-(n)";
                break;
            default: // outgoing
                $pattern = "(n)-{$relationPattern}->({$targetVariable}:{$targetLabel})";
                break;
        }

        $this->queryBuilder->match($pattern);

        return $this;
    }

    public function returnClause(string $expression): self
    {
        $this->queryBuilder->returnClause($expression);

        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->queryBuilder->orderBy($field, $direction);

        return $this;
    }

    public function limit(int $count): self
    {
        $this->queryBuilder->limit($count);

        return $this;
    }

    public function skip(int $count): self
    {
        $this->queryBuilder->skip($count);

        return $this;
    }

    public function get(): Collection
    {
        $query = $this->queryBuilder->build();
        if (!str_contains(strtoupper($query), 'RETURN')) {
            $this->queryBuilder->returnClause('n');
        }

        $result = $this->execute();
        if ($result instanceof SummarizedResult) {
            return collect();
        }

        return $this->processResults($result);
    }

    public function first(): ?object
    {
        $this->limit(1);
        $result = $this->execute();
        if ($result instanceof SummarizedResult) {
            return null;
        }
        $processed = $this->processResults($result);

        return $processed->first();
    }

    public function all(): array
    {
        return $this->get()->all();
    }

    public function count(): int
    {
        $originalQuery = $this->queryBuilder->build();
        $this->queryBuilder->reset();

        if (preg_match('/MATCH\s+(.+?)(?:\s+WHERE|\s+RETURN|\s+ORDER|\s+LIMIT|$)/i', $originalQuery, $matches)) {
            $this->queryBuilder->match($matches[1]);
        }

        $this->queryBuilder->returnClause('count(n) as count');

        $result = $this->execute();
        $record = $result->first();

        return $record ? $record->get('count') : 0;
    }

    public function delete(): int
    {
        $this->queryBuilder->delete('n');
        $result = $this->execute();

        return $result->getSummary()->getCounters()->nodesDeleted();
    }

    public function update(array $properties): int
    {
        $setParts = [];
        foreach ($properties as $key => $value) {
            $paramKey = $this->queryBuilder->addParameter($value);
            $setParts[] = "n.{$key} = \${$paramKey}";
        }

        if (!empty($setParts)) {
            $this->queryBuilder->set(implode(', ', $setParts))
                ->returnClause('n');
        }

        $result = $this->execute();

        return $result->getSummary()->getCounters()->propertiesSet();
    }

    public function chunkResult(string $fieldKey = '', string $fieldValue = ''): array
    {
        $results = $this->get();
        if (empty($fieldKey) && empty($fieldValue)) {
            return $results->toArray();
        }
        return $results->pluck($fieldValue, $fieldKey)->all();
    }

    public function treeResult(string $groupBy): Collection
    {
        return $this->get()->groupBy($groupBy);
    }

    public function keyBy(string $field): Collection
    {
        return $this->get()->keyBy($field);
    }

    private function execute(): Result|SummarizedResult
    {
        $query = $this->queryBuilder->build();
        $parameters = $this->queryBuilder->getParameters();

        $this->logQuery($query, $parameters);

        try {
            return $this->client->run($query, $parameters);
        } catch (\Exception $e) {
            $this->logQueryError($query, $parameters, $e);
            throw new RuntimeException('Graph query failed: '.$e->getMessage(), 0, $e);
        }
    }

    private function processResults(Result $result): Collection
    {
        $results = collect();

        foreach ($result as $record) {
            $data = [];

            foreach ($record->keys() as $key) {
                $value = $record->get($key);
                $data[$key] = $this->processValue($value);
            }

            $results->push((object) $data);
        }

        return $results;
    }

    private function processValue($value)
    {
        if ($value instanceof Node) {
            return $this->nodeToObject($value);
        } elseif ($value instanceof Relationship) {
            return $this->relationshipToObject($value);
        } elseif ($value instanceof CypherMap) {
            return $this->mapToObject($value);
        } elseif (is_array($value)) {
            return array_map([$this, 'processValue'], $value);
        }

        return $value;
    }

    private function nodeToObject(Node $node): object
    {
        $data = [
            'id' => $node->getId(),
            'labels' => $node->getLabels()->toArray(),
            'properties' => $node->getProperties()->toArray(),
        ];

        return (object) array_merge($data, $data['properties']);
    }

    private function relationshipToObject(Relationship $relationship): object
    {
        $data = [
            'id' => $relationship->getId(),
            'type' => $relationship->getType(),
            'start_node_id' => $relationship->getStartNodeId(),
            'end_node_id' => $relationship->getEndNodeId(),
            'properties' => $relationship->getProperties()->toArray(),
        ];

        return (object) array_merge($data, $data['properties']);
    }

    private function mapToObject(CypherMap $map): object
    {
        return (object) $map->toArray();
    }

    private function logQuery(string $query, array $parameters): void
    {
        if (!$this->logQueries) {
            return;
        }

        Log::channel(config('webtool.neo4j.logging.channel', 'daily'))
            ->log(config('webtool.neo4j.logging.level', 'debug'), 'Neo4j Query Executed', [
                'connection' => $this->connectionName,
                'query' => $query,
                'parameters' => $parameters,
                'time' => microtime(true),
            ]);
    }

    private function logQueryError(string $query, array $parameters, \Exception $e): void
    {
        Log::channel(config('webtool.neo4j.logging.channel', 'daily'))
            ->error('Neo4j Query Failed', [
                'connection' => $this->connectionName,
                'query' => $query,
                'parameters' => $parameters,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
    }

    public function getQueryBuilder(): QueryBuilderService
    {
        return $this->queryBuilder;
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }
}