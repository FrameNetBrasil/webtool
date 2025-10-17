<?php

namespace App\Services;

use App\Services\Neo4j\ConnectionService;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Illuminate\Support\Facades\Log;
use Exception;

class Neo4jService
{
    private ClientInterface $client;

    public function __construct(?string $connectionName = null)
    {
        if (!ConnectionService::isEnabled()) {
            throw new \RuntimeException('Neo4j is not enabled. Set NEO4J_ENABLED=true in your environment.');
        }

        $this->client = ConnectionService::connection($connectionName);
    }

    public function isConnected(): bool
    {
        try {
            $result = $this->client->run('RETURN 1 as test');
            return $result->count() > 0;
        } catch (Exception $e) {
            Log::warning('Neo4j connection check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function run(string $query, array $parameters = []): CypherList
    {
        try {
            return $this->client->run($query, $parameters);
        } catch (Exception $e) {
            Log::error('Neo4j query failed', [
                'query' => $query,
                'parameters' => $parameters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function runStatement(Statement $statement): CypherList
    {
        try {
            return $this->client->runStatement($statement);
        } catch (Exception $e) {
            Log::error('Neo4j statement failed', [
                'statement' => $statement->getText(),
                'parameters' => $statement->getParameters(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function runStatements(array $statements): CypherList
    {
        try {
            return $this->client->runStatements($statements);
        } catch (Exception $e) {
            Log::error('Neo4j statements failed', [
                'statements_count' => count($statements),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createNode(string $label, array $properties = []): ?CypherMap
    {
        $query = "CREATE (n:$label \$props) RETURN n";
        $result = $this->run($query, ['props' => $properties]);
        return $result->isEmpty() ? null : $result->first()->get('n');
    }

    public function findNode(string $label, array $properties = []): ?CypherMap
    {
        $whereClause = '';
        if (!empty($properties)) {
            $conditions = [];
            foreach (array_keys($properties) as $key) {
                $conditions[] = "n.$key = \$$key";
            }
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        $query = "MATCH (n:$label) $whereClause RETURN n LIMIT 1";
        $result = $this->run($query, $properties);
        return $result->isEmpty() ? null : $result->first()->get('n');
    }

    public function findNodes(string $label, array $properties = [], int $limit = null): CypherList
    {
        $whereClause = '';
        if (!empty($properties)) {
            $conditions = [];
            foreach (array_keys($properties) as $key) {
                $conditions[] = "n.$key = \$$key";
            }
            $whereClause = 'WHERE ' . implode(' AND ', $conditions);
        }

        $limitClause = $limit ? "LIMIT $limit" : '';
        $query = "MATCH (n:$label) $whereClause RETURN n $limitClause";
        return $this->run($query, $properties);
    }

    public function updateNode(string $label, array $matchProperties, array $updateProperties): ?CypherMap
    {
        $whereConditions = [];
        foreach (array_keys($matchProperties) as $key) {
            $whereConditions[] = "n.$key = \$match_$key";
        }
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

        $setClause = [];
        foreach (array_keys($updateProperties) as $key) {
            $setClause[] = "n.$key = \$update_$key";
        }
        $setClause = 'SET ' . implode(', ', $setClause);

        $query = "MATCH (n:$label) $whereClause $setClause RETURN n";

        $parameters = [];
        foreach ($matchProperties as $key => $value) {
            $parameters["match_$key"] = $value;
        }
        foreach ($updateProperties as $key => $value) {
            $parameters["update_$key"] = $value;
        }

        $result = $this->run($query, $parameters);
        return $result->isEmpty() ? null : $result->first()->get('n');
    }

    public function deleteNode(string $label, array $properties): bool
    {
        $whereConditions = [];
        foreach (array_keys($properties) as $key) {
            $whereConditions[] = "n.$key = \$$key";
        }
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);

        $query = "MATCH (n:$label) $whereClause DELETE n";
        $result = $this->run($query, $properties);
        return true;
    }

    public function createRelationship(
        string $startLabel,
        array $startProperties,
        string $endLabel,
        array $endProperties,
        string $relationshipType,
        array $relationshipProperties = []
    ): ?CypherMap {
        $startWhereConditions = [];
        foreach (array_keys($startProperties) as $key) {
            $startWhereConditions[] = "start.$key = \$start_$key";
        }
        $startWhereClause = 'WHERE ' . implode(' AND ', $startWhereConditions);

        $endWhereConditions = [];
        foreach (array_keys($endProperties) as $key) {
            $endWhereConditions[] = "end.$key = \$end_$key";
        }
        $endWhereClause = 'WHERE ' . implode(' AND ', $endWhereConditions);

        $relPropsClause = empty($relationshipProperties) ? '' : ' $relProps';
        $query = "MATCH (start:$startLabel) $startWhereClause
                  MATCH (end:$endLabel) $endWhereClause
                  CREATE (start)-[r:$relationshipType$relPropsClause]->(end)
                  RETURN r";

        $parameters = [];
        foreach ($startProperties as $key => $value) {
            $parameters["start_$key"] = $value;
        }
        foreach ($endProperties as $key => $value) {
            $parameters["end_$key"] = $value;
        }
        if (!empty($relationshipProperties)) {
            $parameters['relProps'] = $relationshipProperties;
        }

        $result = $this->run($query, $parameters);
        return $result->isEmpty() ? null : $result->first()->get('r');
    }

    public function clearDatabase(): bool
    {
        try {
            $this->run('MATCH (n) DETACH DELETE n');
            return true;
        } catch (Exception $e) {
            Log::error('Failed to clear Neo4j database', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getDatabaseInfo(): array
    {
        try {
            $nodeCount = $this->run('MATCH (n) RETURN count(n) as nodeCount')->first()->get('nodeCount');
            $relationshipCount = $this->run('MATCH ()-[r]->() RETURN count(r) as relCount')->first()->get('relCount');
            $labels = $this->run('CALL db.labels()')->pluck('label')->toArray();
            $relationshipTypes = $this->run('CALL db.relationshipTypes()')->pluck('relationshipType')->toArray();

            return [
                'nodeCount' => $nodeCount,
                'relationshipCount' => $relationshipCount,
                'labels' => $labels,
                'relationshipTypes' => $relationshipTypes,
                'connected' => true
            ];
        } catch (Exception $e) {
            Log::error('Failed to get Neo4j database info', ['error' => $e->getMessage()]);
            return [
                'nodeCount' => 0,
                'relationshipCount' => 0,
                'labels' => [],
                'relationshipTypes' => [],
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}