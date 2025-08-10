<?php

namespace Phariscope\EventStore\Performance;

/**
 * Performance metrics collector for EventStore operations.
 *
 * This class tracks various metrics about event store performance,
 * including operation times, event counts, and memory usage.
 */
class EventStoreMetrics
{
    /** @var array<string, array{type: string, start: float, memory_start: int}> */
    private array $operationTimes = [];

    /** @var array<string, array<int, array{duration: float, event_count: int, memory_used: int, timestamp: int}>> */
    private array $eventCounts = [];

    private int $totalOperations = 0;
    private float $totalTime = 0.0;
    private int $peakMemoryUsage = 0;

    /**
     * Start timing an operation.
     */
    public function startOperation(string $operationType): string
    {
        $operationId = uniqid($operationType . '_', true);
        $this->operationTimes[$operationId] = [
            'type' => $operationType,
            'start' => microtime(true),
            'memory_start' => memory_get_usage(true)
        ];

        return $operationId;
    }

    /**
     * End timing an operation and record the metrics.
     */
    public function endOperation(string $operationId, int $eventCount = 1): void
    {
        if (!isset($this->operationTimes[$operationId])) {
            throw new \InvalidArgumentException("Operation ID '{$operationId}' not found");
        }

        $operation = $this->operationTimes[$operationId];
        $duration = microtime(true) - $operation['start'];
        $memoryUsed = memory_get_usage(true) - $operation['memory_start'];

        $operationType = $operation['type'];

        if (!isset($this->eventCounts[$operationType])) {
            $this->eventCounts[$operationType] = [];
        }

        $this->eventCounts[$operationType][] = [
            'duration' => $duration,
            'event_count' => $eventCount,
            'memory_used' => $memoryUsed,
            'timestamp' => time()
        ];

        $this->totalOperations++;
        $this->totalTime += $duration;
        $this->peakMemoryUsage = max($this->peakMemoryUsage, memory_get_peak_usage(true));

        unset($this->operationTimes[$operationId]);
    }

    /**
     * Get average operation time for a specific operation type.
     */
    public function getAverageOperationTime(string $operationType): float
    {
        if (!isset($this->eventCounts[$operationType])) {
            return 0.0;
        }

        $operations = $this->eventCounts[$operationType];
        $totalTime = array_sum(array_column($operations, 'duration'));

        return $totalTime / count($operations);
    }

    /**
     * Get throughput (events per second) for a specific operation type.
     */
    public function getThroughput(string $operationType): float
    {
        if (!isset($this->eventCounts[$operationType])) {
            return 0.0;
        }

        $operations = $this->eventCounts[$operationType];
        $totalEvents = array_sum(array_column($operations, 'event_count'));
        $totalTime = array_sum(array_column($operations, 'duration'));

        return $totalTime > 0 ? $totalEvents / $totalTime : 0.0;
    }

        /**
     * Get memory usage statistics for a specific operation type.
     * @return array{avg: float, max: int, min: int}
     */
    public function getMemoryStats(string $operationType): array
    {
        if (!isset($this->eventCounts[$operationType])) {
            return ['avg' => 0.0, 'max' => 0, 'min' => 0];
        }

        $memoryUsages = array_column($this->eventCounts[$operationType], 'memory_used');

        if (empty($memoryUsages)) {
            return ['avg' => 0.0, 'max' => 0, 'min' => 0];
        }

        return [
            'avg' => array_sum($memoryUsages) / count($memoryUsages),
            'max' => max($memoryUsages),
            'min' => min($memoryUsages)
        ];
    }

    /**
     * Get comprehensive performance report.
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
        $report = [
            'summary' => [
                'total_operations' => $this->totalOperations,
                'total_time' => $this->totalTime,
                'average_operation_time' => $this->totalOperations > 0 ? $this->totalTime / $this->totalOperations : 0,
                'peak_memory_usage' => $this->peakMemoryUsage
            ],
            'by_operation' => []
        ];

        foreach (array_keys($this->eventCounts) as $operationType) {
            $report['by_operation'][$operationType] = [
                'operation_count' => count($this->eventCounts[$operationType]),
                'average_time' => $this->getAverageOperationTime($operationType),
                'throughput' => $this->getThroughput($operationType),
                'memory_stats' => $this->getMemoryStats($operationType)
            ];
        }

        return $report;
    }

    /**
     * Expose raw entries for a given operation type to support testing of event_count and timings.
     * @return array<int, array{duration: float, event_count: int, memory_used: int, timestamp: int}>
     */
    public function getOperationEntries(string $operationType): array
    {
        return $this->eventCounts[$operationType] ?? [];
    }

    /**
     * Reset all metrics.
     */
    public function reset(): void
    {
        $this->operationTimes = [];
        $this->eventCounts = [];
        $this->totalOperations = 0;
        $this->totalTime = 0.0;
        $this->peakMemoryUsage = 0;
    }

    /**
     * Export metrics to JSON format.
     */
    public function exportToJson(): string
    {
        $result = json_encode($this->getPerformanceReport(), JSON_PRETTY_PRINT);
        return $result !== false ? $result : '{}';
    }
}
