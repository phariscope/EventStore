<?php

namespace Phariscope\EventStore\Tests\Persistence;

use Phariscope\EventStore\Performance\EventStoreMetrics;
use Phariscope\EventStore\Persistence\StoreEventInMemory;
use Phariscope\EventStore\Persistence\StoreEventWithMetrics;
use PHPUnit\Framework\TestCase;

class StoreEventWithMetricsTest extends TestCase
{
    private StoreEventWithMetrics $store;
    private EventStoreMetrics $metrics;

    protected function setUp(): void
    {
        $baseStore = new StoreEventInMemory();
        $this->metrics = new EventStoreMetrics();
        $this->store = new StoreEventWithMetrics($baseStore, $this->metrics);
    }

    public function testAppendWithMetrics(): void
    {
        $event = new EventSent("test");

        $this->store->append($event);

        $report = $this->store->getPerformanceReport();

        $this->assertEquals(1, $report['summary']['total_operations']);
        $this->assertArrayHasKey('append', $report['by_operation']);
        $this->assertEquals(1, $report['by_operation']['append']['operation_count']);
    }

    public function testAllStoredEventsSinceWithMetrics(): void
    {
        // Add some events first
        for ($i = 1; $i <= 5; $i++) {
            $this->store->append(new EventSent("event{$i}"));
        }

        // Reset metrics to focus on the query operation
        $this->metrics->reset();

        $events = $this->store->allStoredEventsSince(3);

        $this->assertEquals(3, count($events));

        $report = $this->store->getPerformanceReport();
        $this->assertArrayHasKey('allStoredEventsSince', $report['by_operation']);
        $this->assertEquals(1, $report['by_operation']['allStoredEventsSince']['operation_count']);

        // Validate that event_count is correctly recorded in metrics entries
        $entries = $this->metrics->getOperationEntries('allStoredEventsSince');
        $this->assertNotEmpty($entries);
        $this->assertEquals(3, $entries[array_key_first($entries)]['event_count']);
    }

    public function testLastEventWithMetrics(): void
    {
        $this->store->append(new EventSent("test"));

        // Reset metrics
        $this->metrics->reset();

        $lastEvent = $this->store->lastEvent();

        $this->assertStringContainsString('test', $lastEvent->getEventBody());

        $report = $this->store->getPerformanceReport();
        $this->assertArrayHasKey('lastEvent', $report['by_operation']);
    }

    public function testGetEventsByTypeWithMetrics(): void
    {
        $this->store->append(new EventSent("test1"));
        $this->store->append(new EventSent("test2"));

        // Reset metrics
        $this->metrics->reset();

        $events = $this->store->getEventsByType(EventSent::class);

        $this->assertEquals(2, count($events));

        $report = $this->store->getPerformanceReport();
        $this->assertArrayHasKey('getEventsByType', $report['by_operation']);
        $this->assertEquals(1, $report['by_operation']['getEventsByType']['operation_count']);
    }

    public function testGetWrappedStore(): void
    {
        $wrappedStore = $this->store->getWrappedStore();

        $this->assertInstanceOf(StoreEventInMemory::class, $wrappedStore);
    }

    public function testGetMetrics(): void
    {
        $metrics = $this->store->getMetrics();

        $this->assertSame($this->metrics, $metrics);
    }

    public function testExceptionHandlingInMetrics(): void
    {
        // Create a store that will throw an exception
        $faultyStore = new class implements \Phariscope\EventStore\StoreInterface {
            public function append(\Phariscope\Event\Psr14\Event $event): void
            {
                throw new \RuntimeException('Simulated failure');
            }

            public function allStoredEventsSince(\DateTimeImmutable|int $past): array
            {
                throw new \RuntimeException('Simulated failure');
            }

            public function lastEvent(): \Phariscope\EventStore\StoredEvent
            {
                throw new \RuntimeException('Simulated failure');
            }

            public function getEventsByType(string $eventType, \DateTimeImmutable|int|null $since = null): array
            {
                throw new \RuntimeException('Simulated failure');
            }
        };

        $metricsStore = new StoreEventWithMetrics($faultyStore, $this->metrics);

        try {
            $metricsStore->append(new EventSent("test"));
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            // Expected exception
            $this->assertEquals('Simulated failure', $e->getMessage());
        }

        // Metrics should still be recorded even when operation fails
        $report = $metricsStore->getPerformanceReport();
        $this->assertArrayHasKey('append', $report['by_operation']);
    }

    public function testDefaultMetricsCreation(): void
    {
        $baseStore = new StoreEventInMemory();
        $store = new StoreEventWithMetrics($baseStore); // No metrics provided

        $store->append(new EventSent("test"));

        $report = $store->getPerformanceReport();
        $this->assertEquals(1, $report['summary']['total_operations']);
    }

    public function testAppendExceptionHandling(): void
    {
        $faultyStore = new class implements \Phariscope\EventStore\StoreInterface {
            public function append(\Phariscope\Event\Psr14\Event $event): void
            {
                throw new \RuntimeException('Append failed');
            }
            public function allStoredEventsSince(\DateTimeImmutable|int $past): array
            {
                return [];
            }
            public function lastEvent(): \Phariscope\EventStore\StoredEvent
            {
                throw new \RuntimeException('No events');
            }
            public function getEventsByType(string $eventType, \DateTimeImmutable|int|null $since = null): array
            {
                return [];
            }
        };

        $metrics = new EventStoreMetrics();
        $store = new StoreEventWithMetrics($faultyStore, $metrics);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Append failed');

        try {
            $store->append(new EventSent("test"));
        } catch (\RuntimeException $e) {
            // Verify metrics were still recorded
            $report = $store->getPerformanceReport();
            $this->assertEquals(1, $report['summary']['total_operations']);
            $this->assertArrayHasKey('append', $report['by_operation']);
            throw $e;
        }
    }

    public function testAllStoredEventsSinceExceptionHandling(): void
    {
        $faultyStore = new class implements \Phariscope\EventStore\StoreInterface {
            public function append(\Phariscope\Event\Psr14\Event $event): void
            {
            }
            public function allStoredEventsSince(\DateTimeImmutable|int $past): array
            {
                throw new \RuntimeException('Query failed');
            }
            public function lastEvent(): \Phariscope\EventStore\StoredEvent
            {
                throw new \RuntimeException('No events');
            }
            public function getEventsByType(string $eventType, \DateTimeImmutable|int|null $since = null): array
            {
                return [];
            }
        };

        $metrics = new EventStoreMetrics();
        $store = new StoreEventWithMetrics($faultyStore, $metrics);

        try {
            $store->allStoredEventsSince(10);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            // Verify metrics were recorded with 0 events
            $report = $store->getPerformanceReport();
            $this->assertEquals(1, $report['summary']['total_operations']);
            $this->assertEquals('Query failed', $e->getMessage());
        }
    }

    public function testLastEventExceptionHandling(): void
    {
        $faultyStore = new class implements \Phariscope\EventStore\StoreInterface {
            public function append(\Phariscope\Event\Psr14\Event $event): void
            {
            }
            public function allStoredEventsSince(\DateTimeImmutable|int $past): array
            {
                return [];
            }
            public function lastEvent(): \Phariscope\EventStore\StoredEvent
            {
                throw new \Phariscope\EventStore\Exceptions\EventNotFoundException('No events found');
            }
            public function getEventsByType(string $eventType, \DateTimeImmutable|int|null $since = null): array
            {
                return [];
            }
        };

        $metrics = new EventStoreMetrics();
        $store = new StoreEventWithMetrics($faultyStore, $metrics);

        $this->expectException(\Phariscope\EventStore\Exceptions\EventNotFoundException::class);

        try {
            $store->lastEvent();
        } catch (\Phariscope\EventStore\Exceptions\EventNotFoundException $e) {
            // Verify metrics were recorded
            $report = $store->getPerformanceReport();
            $this->assertEquals(1, $report['summary']['total_operations']);
            throw $e;
        }
    }

    public function testGetEventsByTypeExceptionHandling(): void
    {
        $faultyStore = new class implements \Phariscope\EventStore\StoreInterface {
            public function append(\Phariscope\Event\Psr14\Event $event): void
            {
            }
            public function allStoredEventsSince(\DateTimeImmutable|int $past): array
            {
                return [];
            }
            public function lastEvent(): \Phariscope\EventStore\StoredEvent
            {
                throw new \RuntimeException('No events');
            }
            public function getEventsByType(string $eventType, \DateTimeImmutable|int|null $since = null): array
            {
                throw new \InvalidArgumentException('Invalid type');
            }
        };

        $metrics = new EventStoreMetrics();
        $store = new StoreEventWithMetrics($faultyStore, $metrics);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid type');

        try {
            $store->getEventsByType('InvalidType');
        } catch (\InvalidArgumentException $e) {
            // Verify metrics were recorded
            $report = $store->getPerformanceReport();
            $this->assertEquals(1, $report['summary']['total_operations']);
            throw $e;
        }
    }
}
