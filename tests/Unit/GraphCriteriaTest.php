<?php

namespace Tests\Unit;

use App\Database\GraphCriteria;
use App\Services\Neo4j\ConnectionService;
use App\Services\Neo4j\QueryBuilderService;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\Node;
use Mockery;
use Tests\TestCase;

class GraphCriteriaTest extends TestCase
{
    private $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        config(['webtool.neo4j.enabled' => true]);

        $this->mockClient = Mockery::mock(ClientInterface::class);

        ConnectionService::closeAll();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_throws_exception_when_neo4j_disabled(): void
    {
        config(['webtool.neo4j.enabled' => false]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Neo4j is not enabled');

        new GraphCriteria();
    }

    public function test_can_create_node_query(): void
    {
        $this->mockConnectionService();

        $criteria = GraphCriteria::node('Frame');
        $queryBuilder = $criteria->getQueryBuilder();

        $query = $queryBuilder->build();

        $this->assertStringContains('MATCH (n:Frame)', $query);
    }

    public function test_can_create_match_query(): void
    {
        $this->mockConnectionService();

        $criteria = GraphCriteria::match('(n:Frame)-[:HAS_ELEMENT]->(fe:FrameElement)');
        $queryBuilder = $criteria->getQueryBuilder();

        $query = $queryBuilder->build();

        $this->assertStringContains('MATCH (n:Frame)-[:HAS_ELEMENT]->(fe:FrameElement)', $query);
    }

    public function test_can_add_filter_conditions(): void
    {
        $this->mockConnectionService();

        $criteria = GraphCriteria::byFilter('Frame', [
            ['n.name', '=', 'TestFrame'],
            ['n.active', '=', true]
        ]);

        $queryBuilder = $criteria->getQueryBuilder();
        $query = $queryBuilder->build();
        $parameters = $queryBuilder->getParameters();

        $this->assertStringContains('MATCH (n:Frame)', $query);
        $this->assertStringContains('WHERE', $query);
        $this->assertEquals('TestFrame', $parameters['param_0']);
        $this->assertTrue($parameters['param_1']);
    }

    public function test_can_add_language_filter(): void
    {
        $this->mockConnectionService();

        $criteria = GraphCriteria::byFilterLanguage('Frame', [
            ['n.name', '=', 'TestFrame']
        ], 'idLanguage', 1);

        $queryBuilder = $criteria->getQueryBuilder();
        $query = $queryBuilder->build();
        $parameters = $queryBuilder->getParameters();

        $this->assertStringContains('WHERE n.idLanguage = $param_0', $query);
        $this->assertEquals(1, $parameters['param_0']);
    }

    public function test_can_add_relationship_traversal(): void
    {
        $this->mockConnectionService();

        $criteria = GraphCriteria::node('Frame')
            ->withRelations('HAS_ELEMENT', 'outgoing', 'FrameElement');

        $queryBuilder = $criteria->getQueryBuilder();
        $query = $queryBuilder->build();

        $this->assertStringContains('MATCH (n)-[r:HAS_ELEMENT]->(m:FrameElement)', $query);
    }

    public function test_can_add_bidirectional_relationships(): void
    {
        $this->mockConnectionService();

        $criteria = GraphCriteria::node('Frame')
            ->withRelations('RELATED_TO', 'bidirectional', 'Frame');

        $queryBuilder = $criteria->getQueryBuilder();
        $query = $queryBuilder->build();

        $this->assertStringContains('(m:Frame)-[r:RELATED_TO]-(n)', $query);
    }

    public function test_can_add_incoming_relationships(): void
    {
        $this->mockConnectionService();

        $criteria = GraphCriteria::node('Frame')
            ->withRelations('INHERITS_FROM', 'incoming', 'Frame');

        $queryBuilder = $criteria->getQueryBuilder();
        $query = $queryBuilder->build();

        $this->assertStringContains('(m:Frame)-[r:INHERITS_FROM]->(n)', $query);
    }

    public function test_can_build_complex_query_with_filters_and_ordering(): void
    {
        $this->mockConnectionService();

        $criteria = GraphCriteria::node('Frame')
            ->where('n.name', 'CONTAINS', 'Test')
            ->orderBy('n.name', 'DESC')
            ->limit(10)
            ->skip(5);

        $queryBuilder = $criteria->getQueryBuilder();
        $query = $queryBuilder->build();
        $parameters = $queryBuilder->getParameters();

        $this->assertStringContains('MATCH (n:Frame)', $query);
        $this->assertStringContains('WHERE n.name CONTAINS $param_0', $query);
        $this->assertStringContains('ORDER BY n.name DESC', $query);
        $this->assertStringContains('LIMIT 10', $query);
        $this->assertStringContains('SKIP 5', $query);
        $this->assertEquals('Test', $parameters['param_0']);
    }

    public function test_filter_method_handles_empty_arrays(): void
    {
        $this->mockConnectionService();

        $criteria = GraphCriteria::node('Frame')
            ->filter([]);

        $queryBuilder = $criteria->getQueryBuilder();
        $query = $queryBuilder->build();

        $this->assertStringNotContains('WHERE', $query);
    }

    public function test_filter_method_handles_single_condition(): void
    {
        $this->mockConnectionService();

        $criteria = GraphCriteria::node('Frame')
            ->filter(['n.name', '=', 'TestFrame']);

        $queryBuilder = $criteria->getQueryBuilder();
        $query = $queryBuilder->build();

        $this->assertStringContains('WHERE n.name = $param_0', $query);
    }

    public function test_filter_method_skips_null_values(): void
    {
        $this->mockConnectionService();

        $criteria = GraphCriteria::node('Frame')
            ->filter([
                ['n.name', '=', 'TestFrame'],
                ['n.description', '=', null],
                ['n.active', '=', true]
            ]);

        $queryBuilder = $criteria->getQueryBuilder();
        $parameters = $queryBuilder->getParameters();

        $this->assertCount(2, $parameters);
        $this->assertEquals('TestFrame', $parameters['param_0']);
        $this->assertTrue($parameters['param_1']);
    }

    private function mockConnectionService(): void
    {
        $this->partialMock(ConnectionService::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('connection')->andReturn($this->mockClient);
        });
    }
}
