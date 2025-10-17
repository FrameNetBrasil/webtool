<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Neo4jService;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Mockery;

class Neo4jServiceTest extends TestCase
{
    private $mockClient;
    private Neo4jService $neo4jService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = Mockery::mock(ClientInterface::class);
        $this->neo4jService = new Neo4jService($this->mockClient);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_is_connected_returns_true_when_connection_successful(): void
    {
        $mockResult = Mockery::mock(CypherList::class);
        $mockResult->shouldReceive('count')->andReturn(1);

        $this->mockClient
            ->shouldReceive('run')
            ->with('RETURN 1 as test')
            ->andReturn($mockResult);

        $this->assertTrue($this->neo4jService->isConnected());
    }

    public function test_is_connected_returns_false_when_connection_fails(): void
    {
        $this->mockClient
            ->shouldReceive('run')
            ->with('RETURN 1 as test')
            ->andThrow(new \Exception('Connection failed'));

        $this->assertFalse($this->neo4jService->isConnected());
    }

    public function test_run_executes_query_successfully(): void
    {
        $query = 'MATCH (n) RETURN n';
        $parameters = ['id' => 1];
        $mockResult = Mockery::mock(CypherList::class);

        $this->mockClient
            ->shouldReceive('run')
            ->with($query, $parameters)
            ->andReturn($mockResult);

        $result = $this->neo4jService->run($query, $parameters);

        $this->assertSame($mockResult, $result);
    }

    public function test_create_node_returns_created_node(): void
    {
        $label = 'Frame';
        $properties = ['name' => 'Test Frame', 'id' => 1];
        $query = "CREATE (n:$label \$props) RETURN n";

        $mockNode = Mockery::mock(CypherMap::class);
        $mockRecord = Mockery::mock();
        $mockRecord->shouldReceive('get')->with('n')->andReturn($mockNode);

        $mockResult = Mockery::mock(CypherList::class);
        $mockResult->shouldReceive('isEmpty')->andReturn(false);
        $mockResult->shouldReceive('first')->andReturn($mockRecord);

        $this->mockClient
            ->shouldReceive('run')
            ->with($query, ['props' => $properties])
            ->andReturn($mockResult);

        $result = $this->neo4jService->createNode($label, $properties);

        $this->assertSame($mockNode, $result);
    }

    public function test_create_node_returns_null_when_no_result(): void
    {
        $label = 'Frame';
        $properties = ['name' => 'Test Frame'];
        $query = "CREATE (n:$label \$props) RETURN n";

        $mockResult = Mockery::mock(CypherList::class);
        $mockResult->shouldReceive('isEmpty')->andReturn(true);

        $this->mockClient
            ->shouldReceive('run')
            ->with($query, ['props' => $properties])
            ->andReturn($mockResult);

        $result = $this->neo4jService->createNode($label, $properties);

        $this->assertNull($result);
    }

    public function test_find_node_returns_found_node(): void
    {
        $label = 'Frame';
        $properties = ['id' => 1];
        $query = "MATCH (n:$label) WHERE n.id = \$id RETURN n LIMIT 1";

        $mockNode = Mockery::mock(CypherMap::class);
        $mockRecord = Mockery::mock();
        $mockRecord->shouldReceive('get')->with('n')->andReturn($mockNode);

        $mockResult = Mockery::mock(CypherList::class);
        $mockResult->shouldReceive('isEmpty')->andReturn(false);
        $mockResult->shouldReceive('first')->andReturn($mockRecord);

        $this->mockClient
            ->shouldReceive('run')
            ->with($query, $properties)
            ->andReturn($mockResult);

        $result = $this->neo4jService->findNode($label, $properties);

        $this->assertSame($mockNode, $result);
    }

    public function test_find_node_returns_null_when_not_found(): void
    {
        $label = 'Frame';
        $properties = ['id' => 999];
        $query = "MATCH (n:$label) WHERE n.id = \$id RETURN n LIMIT 1";

        $mockResult = Mockery::mock(CypherList::class);
        $mockResult->shouldReceive('isEmpty')->andReturn(true);

        $this->mockClient
            ->shouldReceive('run')
            ->with($query, $properties)
            ->andReturn($mockResult);

        $result = $this->neo4jService->findNode($label, $properties);

        $this->assertNull($result);
    }

    public function test_delete_node_executes_successfully(): void
    {
        $label = 'Frame';
        $properties = ['id' => 1];
        $query = "MATCH (n:$label) WHERE n.id = \$id DELETE n";

        $mockResult = Mockery::mock(CypherList::class);

        $this->mockClient
            ->shouldReceive('run')
            ->with($query, $properties)
            ->andReturn($mockResult);

        $result = $this->neo4jService->deleteNode($label, $properties);

        $this->assertTrue($result);
    }

    public function test_clear_database_executes_successfully(): void
    {
        $mockResult = Mockery::mock(CypherList::class);

        $this->mockClient
            ->shouldReceive('run')
            ->with('MATCH (n) DETACH DELETE n')
            ->andReturn($mockResult);

        $result = $this->neo4jService->clearDatabase();

        $this->assertTrue($result);
    }

    public function test_clear_database_returns_false_on_exception(): void
    {
        $this->mockClient
            ->shouldReceive('run')
            ->with('MATCH (n) DETACH DELETE n')
            ->andThrow(new \Exception('Database error'));

        $result = $this->neo4jService->clearDatabase();

        $this->assertFalse($result);
    }

    public function test_get_database_info_returns_correct_structure(): void
    {
        $mockNodeRecord = Mockery::mock();
        $mockNodeRecord->shouldReceive('get')->with('nodeCount')->andReturn(100);

        $mockRelRecord = Mockery::mock();
        $mockRelRecord->shouldReceive('get')->with('relCount')->andReturn(50);

        $mockLabelsResult = Mockery::mock(CypherList::class);
        $mockLabelsResult->shouldReceive('pluck')->with('label')->andReturn(collect(['Frame', 'LexicalUnit']));

        $mockRelTypesResult = Mockery::mock(CypherList::class);
        $mockRelTypesResult->shouldReceive('pluck')->with('relationshipType')->andReturn(collect(['HAS_ELEMENT', 'INHERITS_FROM']));

        $mockNodeResult = Mockery::mock(CypherList::class);
        $mockNodeResult->shouldReceive('first')->andReturn($mockNodeRecord);

        $mockRelResult = Mockery::mock(CypherList::class);
        $mockRelResult->shouldReceive('first')->andReturn($mockRelRecord);

        $this->mockClient
            ->shouldReceive('run')
            ->with('MATCH (n) RETURN count(n) as nodeCount')
            ->andReturn($mockNodeResult);

        $this->mockClient
            ->shouldReceive('run')
            ->with('MATCH ()-[r]->() RETURN count(r) as relCount')
            ->andReturn($mockRelResult);

        $this->mockClient
            ->shouldReceive('run')
            ->with('CALL db.labels()')
            ->andReturn($mockLabelsResult);

        $this->mockClient
            ->shouldReceive('run')
            ->with('CALL db.relationshipTypes()')
            ->andReturn($mockRelTypesResult);

        $result = $this->neo4jService->getDatabaseInfo();

        $this->assertEquals([
            'nodeCount' => 100,
            'relationshipCount' => 50,
            'labels' => ['Frame', 'LexicalUnit'],
            'relationshipTypes' => ['HAS_ELEMENT', 'INHERITS_FROM'],
            'connected' => true
        ], $result);
    }
}
