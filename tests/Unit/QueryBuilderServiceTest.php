<?php

namespace Tests\Unit;

use App\Services\Neo4j\QueryBuilderService;
use Tests\TestCase;

class QueryBuilderServiceTest extends TestCase
{
    private QueryBuilderService $queryBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->queryBuilder = new QueryBuilderService();
    }

    public function test_can_build_simple_match_query(): void
    {
        $query = $this->queryBuilder
            ->match('(n:Frame)')
            ->returnClause('n')
            ->build();

        $this->assertEquals('MATCH (n:Frame) RETURN n', $query);
    }

    public function test_can_build_create_query(): void
    {
        $query = $this->queryBuilder
            ->create('(n:Frame {name: "Test"})')
            ->returnClause('n')
            ->build();

        $this->assertEquals('CREATE (n:Frame {name: "Test"}) RETURN n', $query);
    }

    public function test_can_add_where_conditions(): void
    {
        $this->queryBuilder
            ->match('(n:Frame)')
            ->whereParameter('n.name', '=', 'TestFrame')
            ->returnClause('n');

        $query = $this->queryBuilder->build();
        $parameters = $this->queryBuilder->getParameters();

        $this->assertEquals('MATCH (n:Frame) WHERE n.name = $param_0 RETURN n', $query);
        $this->assertEquals(['param_0' => 'TestFrame'], $parameters);
    }

    public function test_handles_startswith_operator(): void
    {
        $this->queryBuilder
            ->match('(n:Frame)')
            ->whereParameter('n.name', 'STARTSWITH', 'Test')
            ->returnClause('n');

        $query = $this->queryBuilder->build();

        $this->assertStringContains('n.name STARTS WITH $param_0', $query);
    }

    public function test_handles_contains_operator(): void
    {
        $this->queryBuilder
            ->match('(n:Frame)')
            ->whereParameter('n.name', 'CONTAINS', 'Test')
            ->returnClause('n');

        $query = $this->queryBuilder->build();

        $this->assertStringContains('n.name CONTAINS $param_0', $query);
    }

    public function test_handles_in_operator(): void
    {
        $this->queryBuilder
            ->match('(n:Frame)')
            ->whereParameter('n.id', 'IN', [1, 2, 3])
            ->returnClause('n');

        $query = $this->queryBuilder->build();
        $parameters = $this->queryBuilder->getParameters();

        $this->assertStringContains('n.id IN $param_0', $query);
        $this->assertEquals(['param_0' => [1, 2, 3]], $parameters);
    }

    public function test_handles_null_values(): void
    {
        $this->queryBuilder
            ->match('(n:Frame)')
            ->whereParameter('n.description', '=', null)
            ->returnClause('n');

        $query = $this->queryBuilder->build();

        $this->assertStringContains('n.description IS NULL', $query);
    }

    public function test_handles_not_null_values(): void
    {
        $this->queryBuilder
            ->match('(n:Frame)')
            ->whereParameter('n.description', '=', 'NOT NULL')
            ->returnClause('n');

        $query = $this->queryBuilder->build();

        $this->assertStringContains('n.description IS NOT NULL', $query);
    }

    public function test_can_add_order_by(): void
    {
        $query = $this->queryBuilder
            ->match('(n:Frame)')
            ->returnClause('n')
            ->orderBy('n.name', 'DESC')
            ->build();

        $this->assertEquals('MATCH (n:Frame) RETURN n ORDER BY n.name DESC', $query);
    }

    public function test_can_add_limit(): void
    {
        $query = $this->queryBuilder
            ->match('(n:Frame)')
            ->returnClause('n')
            ->limit(10)
            ->build();

        $this->assertEquals('MATCH (n:Frame) RETURN n LIMIT 10', $query);
    }

    public function test_can_add_skip(): void
    {
        $query = $this->queryBuilder
            ->match('(n:Frame)')
            ->returnClause('n')
            ->skip(5)
            ->build();

        $this->assertEquals('MATCH (n:Frame) RETURN n SKIP 5', $query);
    }

    public function test_can_build_complex_query(): void
    {
        $this->queryBuilder
            ->match('(n:Frame)')
            ->whereParameter('n.name', 'CONTAINS', 'Test')
            ->returnClause('n')
            ->orderBy('n.name')
            ->limit(10);

        $query = $this->queryBuilder->build();
        $parameters = $this->queryBuilder->getParameters();

        $expectedQuery = 'MATCH (n:Frame) WHERE n.name CONTAINS $param_0 RETURN n ORDER BY n.name ASC LIMIT 10';
        $this->assertEquals($expectedQuery, $query);
        $this->assertEquals(['param_0' => 'Test'], $parameters);
    }

    public function test_can_reset_builder(): void
    {
        $this->queryBuilder
            ->match('(n:Frame)')
            ->whereParameter('n.name', '=', 'Test')
            ->returnClause('n');

        $this->queryBuilder->reset();

        $query = $this->queryBuilder->build();
        $parameters = $this->queryBuilder->getParameters();

        $this->assertEquals('', $query);
        $this->assertEquals([], $parameters);
    }

    public function test_node_pattern_helper(): void
    {
        $pattern = QueryBuilderService::nodePattern('n', 'Frame', ['name' => 'Test']);

        $this->assertEquals("(n:Frame {name: 'Test'})", $pattern);
    }

    public function test_relationship_pattern_helper(): void
    {
        $pattern = QueryBuilderService::relationshipPattern('HAS_ELEMENT', 'r', ['weight' => 1]);

        $this->assertEquals("[r:HAS_ELEMENT {weight: 1}]", $pattern);
    }

    public function test_escape_property_helper(): void
    {
        $escaped = QueryBuilderService::escapeProperty('property`name');

        $this->assertEquals('`property``name`', $escaped);
    }
}
