<?php

namespace Phariscope\EventStore\Tests\Performance;

use Phariscope\EventStore\Performance\EventStoreMetrics;
use PHPUnit\Framework\TestCase;

class EventStoreMetricsTest extends TestCase
{
    private EventStoreMetrics $metrics;

    protected function setUp(): void
    {
        $this->metrics = new EventStoreMetrics();
    }

    public function testStartAndEndOperation(): void
    {
                $operationId = $this->metrics->startOperation('test_operation');

        $this->assertStringContainsString('test_operation', $operationId);

        // Simulate some work
        usleep(1000); // 1ms

        $this->metrics->endOperation($operationId, 5);

        $avgTime = $this->metrics->getAverageOperationTime('test_operation');
        $this->assertGreaterThan(0, $avgTime);

        $throughput = $this->metrics->getThroughput('test_operation');
        $this->assertGreaterThan(0, $throughput);
    }

    public function testInvalidOperationId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Operation ID 'invalid_id' not found");

        $this->metrics->endOperation('invalid_id');
    }

    public function testMultipleOperations(): void
    {
        // Record multiple operations of same type
        for ($i = 0; $i < 3; $i++) {
            $operationId = $this->metrics->startOperation('append');
            usleep(500); // 0.5ms
            $this->metrics->endOperation($operationId, 1);
        }

        $report = $this->metrics->getPerformanceReport();

        $this->assertEquals(3, $report['summary']['total_operations']);
        $this->assertEquals(3, $report['by_operation']['append']['operation_count']);
        $this->assertGreaterThan(0, $report['by_operation']['append']['average_time']);
        $this->assertGreaterThan(0, $report['by_operation']['append']['throughput']);
    }

    public function testMemoryStats(): void
    {
        $operationId = $this->metrics->startOperation('memory_test');

        // Allocate some memory
        $data = str_repeat('x', 1000);

        $this->metrics->endOperation($operationId, 1);

                $memoryStats = $this->metrics->getMemoryStats('memory_test');

        $this->assertArrayHasKey('avg', $memoryStats);
        $this->assertArrayHasKey('max', $memoryStats);
        $this->assertArrayHasKey('min', $memoryStats);
    }

    public function testReset(): void
    {
        $operationId = $this->metrics->startOperation('test');
        $this->metrics->endOperation($operationId, 1);

        $reportBefore = $this->metrics->getPerformanceReport();
        $this->assertEquals(1, $reportBefore['summary']['total_operations']);

        $this->metrics->reset();

        $reportAfter = $this->metrics->getPerformanceReport();
        $this->assertEquals(0, $reportAfter['summary']['total_operations']);
    }

    public function testExportToJson(): void
    {
        $operationId = $this->metrics->startOperation('json_test');
        $this->metrics->endOperation($operationId, 1);

                $json = $this->metrics->exportToJson();

        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('by_operation', $data);
    }

    public function testNoOperationsStats(): void
    {
        $avgTime = $this->metrics->getAverageOperationTime('nonexistent');
        $this->assertEquals(0.0, $avgTime);

        $throughput = $this->metrics->getThroughput('nonexistent');
        $this->assertEquals(0.0, $throughput);

        $memoryStats = $this->metrics->getMemoryStats('nonexistent');
        $this->assertEquals(['avg' => 0, 'max' => 0, 'min' => 0], $memoryStats);
    }

    public function testOperationIdFormat(): void
    {
        $operationId = $this->metrics->startOperation('test_operation');

        // Test that operation ID contains the operation type
        $this->assertStringContainsString('test_operation', $operationId);
        $this->assertStringContainsString('_', $operationId);

        $this->metrics->endOperation($operationId, 1);
    }

    public function testDefaultEventCount(): void
    {
        $operationId = $this->metrics->startOperation('default_test');

        // Test default event count (should be 1)
        $this->metrics->endOperation($operationId); // No event count provided

        $report = $this->metrics->getPerformanceReport();
        $this->assertEquals(1, $report['by_operation']['default_test']['operation_count']);
    }

    public function testZeroEventCount(): void
    {
        $operationId = $this->metrics->startOperation('zero_test');
        $this->metrics->endOperation($operationId, 0);

        $throughput = $this->metrics->getThroughput('zero_test');
        $this->assertEquals(0.0, $throughput);
    }

    public function testHighEventCount(): void
    {
        $operationId = $this->metrics->startOperation('bulk_test');
        usleep(1000); // ensure duration > 0 so throughput is defined on fast runtimes
        $this->metrics->endOperation($operationId, 1000);

        $report = $this->metrics->getPerformanceReport();
        $operations = $report['by_operation']['bulk_test'];

        $this->assertEquals(1, $operations['operation_count']);
        $this->assertGreaterThan(0, $operations['throughput']);
    }

    public function testNegativeMemoryUsage(): void
    {
        // Force a scenario where memory usage calculation might be negative
        $operationId = $this->metrics->startOperation('memory_edge_test');

        // Trigger garbage collection to potentially reduce memory
        gc_collect_cycles();

        $this->metrics->endOperation($operationId, 1);

        $memoryStats = $this->metrics->getMemoryStats('memory_edge_test');
        // Memory stats should handle negative values gracefully
        $this->assertArrayHasKey('avg', $memoryStats);
        $this->assertArrayHasKey('max', $memoryStats);
        $this->assertArrayHasKey('min', $memoryStats);
    }

    public function testVeryShortOperation(): void
    {
        $operationId = $this->metrics->startOperation('instant_test');
        // End immediately without any delay
        $this->metrics->endOperation($operationId, 1);

        $avgTime = $this->metrics->getAverageOperationTime('instant_test');
        $this->assertGreaterThanOrEqual(0, $avgTime);

        $throughput = $this->metrics->getThroughput('instant_test');
        $this->assertGreaterThan(0, $throughput);
    }

    public function testConcurrentOperationIds(): void
    {
        $id1 = $this->metrics->startOperation('concurrent1');
        $id2 = $this->metrics->startOperation('concurrent2');

        $this->assertNotEquals($id1, $id2);

        $this->metrics->endOperation($id2, 1);
        $this->metrics->endOperation($id1, 1);

        $report = $this->metrics->getPerformanceReport();
        $this->assertEquals(2, $report['summary']['total_operations']);
    }

    public function testGetMemoryStatsWithEmptyOperations(): void
    {
        // Test the empty memory usages case - this covers the uncovered mutants
        $operationId = $this->metrics->startOperation('empty_memory_test');

        // End operation immediately without any significant memory allocation
        $this->metrics->endOperation($operationId, 0);

        // Force the eventCounts to have empty memory_used array by manipulating internal state
        $reflection = new \ReflectionClass($this->metrics);
        $eventCountsProperty = $reflection->getProperty('eventCounts');
        $eventCountsProperty->setAccessible(true);

        // Set empty memory_used arrays to trigger the empty() condition
        /** @var array<string, array<int, array{duration: float, event_count: int, memory_used: int, timestamp: int}>> $eventCounts */
        $eventCounts = $eventCountsProperty->getValue($this->metrics);
        $eventCounts['empty_memory_test'] = [];
        $eventCountsProperty->setValue($this->metrics, $eventCounts);

        $memoryStats = $this->metrics->getMemoryStats('empty_memory_test');

        // These assertions will catch the uncovered mutants
        $this->assertEquals(0.0, $memoryStats['avg']);
        $this->assertEquals(0, $memoryStats['max']);
        $this->assertEquals(0, $memoryStats['min']);

        // Test with completely non-existent operation type
        $memoryStats = $this->metrics->getMemoryStats('non_existent_operation');
        $this->assertEquals(['avg' => 0.0, 'max' => 0, 'min' => 0], $memoryStats);
    }
}
