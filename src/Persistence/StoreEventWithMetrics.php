<?php

namespace Phariscope\EventStore\Persistence;

use Phariscope\Event\Psr14\Event;
use Phariscope\EventStore\Performance\EventStoreMetrics;
use Phariscope\EventStore\StoredEvent;
use Phariscope\EventStore\StoreInterface;

/**
 * Decorator that adds performance metrics to any EventStore implementation.
 *
 * This class wraps another StoreInterface implementation and automatically
 * tracks performance metrics for all operations.
 */
class StoreEventWithMetrics implements StoreInterface
{
    private StoreInterface $store;
    private EventStoreMetrics $metrics;

    public function __construct(StoreInterface $store, ?EventStoreMetrics $metrics = null)
    {
        $this->store = $store;
        $this->metrics = $metrics ?? new EventStoreMetrics();
    }

    public function append(Event $event): void
    {
        $operationId = $this->metrics->startOperation('append');

        try {
            $this->store->append($event);
            $this->metrics->endOperation($operationId, 1);
        } catch (\Exception $e) {
            $this->metrics->endOperation($operationId, 0);
            throw $e;
        }
    }

    /**
     * @return array<int,StoredEvent>
     */
    public function allStoredEventsSince(\DateTimeImmutable|int $past): array
    {
        $operationId = $this->metrics->startOperation('allStoredEventsSince');

        try {
            $events = $this->store->allStoredEventsSince($past);
            $this->metrics->endOperation($operationId, count($events));
            return $events;
        } catch (\Exception $e) {
            $this->metrics->endOperation($operationId, 0);
            throw $e;
        }
    }

    public function lastEvent(): StoredEvent
    {
        $operationId = $this->metrics->startOperation('lastEvent');

        try {
            $event = $this->store->lastEvent();
            $this->metrics->endOperation($operationId, 1);
            return $event;
        } catch (\Exception $e) {
            $this->metrics->endOperation($operationId, 0);
            throw $e;
        }
    }

    /**
     * Get the performance metrics collector.
     */
    public function getMetrics(): EventStoreMetrics
    {
        return $this->metrics;
    }

    /**
     * Get the wrapped store instance.
     */
    public function getWrappedStore(): StoreInterface
    {
        return $this->store;
    }

    /**
     * @return array<int,StoredEvent>
     */
    public function getEventsByType(string $eventType, \DateTimeImmutable|int|null $since = null): array
    {
        $operationId = $this->metrics->startOperation('getEventsByType');

        try {
            $events = $this->store->getEventsByType($eventType, $since);
            $this->metrics->endOperation($operationId, count($events));
            return $events;
        } catch (\Exception $e) {
            $this->metrics->endOperation($operationId, 0);
            throw $e;
        }
    }

    /**
     * Get a performance report.
     * @return array{
     *     summary: array{
     *         total_operations: int,
     *         total_time: float,
     *         average_operation_time: float,
     *         peak_memory_usage: int
     *     },
     *     by_operation: array<string, array{
     *         operation_count: int,
     *         average_time: float,
     *         throughput: float,
     *         memory_stats: array{avg: float, max: int, min: int}
     *     }>
     * }
     */
    public function getPerformanceReport(): array
    {
        return $this->metrics->getPerformanceReport();
    }
}
