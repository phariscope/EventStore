<?php

namespace Phariscope\EventStore;

use Phariscope\Event\Psr14\Event;
use Phariscope\EventStore\Exceptions\EventNotFoundException;

/**
 * Interface for event storage implementations.
 *
 * This interface defines the contract for storing and retrieving events
 * in an Event Store pattern implementation.
 *
 * @example
 * ```php
 * $store = new StoreEventInMemory();
 * $event = new MyDomainEvent('data');
 *
 * // Store an event
 * $store->append($event);
 *
 * // Retrieve last 10 events
 * $events = $store->allStoredEventsSince(10);
 *
 * // Get the most recent event
 * $lastEvent = $store->lastEvent();
 * ```
 */
interface StoreInterface
{
    /**
     * Appends a new event to the store.
     *
     * @param Event $event The domain event to store
     */
    public function append(Event $event): void;

    /**
     * Retrieves stored events since a given point in time or count.
     *
     * @param \DateTimeImmutable|int $past Either a datetime to get events since that time,
     *                                     or an integer to get the last N events
     * @return array<int,StoredEvent> Array of stored events indexed by their ID
     *
     * @throws \InvalidArgumentException When $past is a negative integer
     */
    public function allStoredEventsSince(\DateTimeImmutable|int $past): array;

    /**
     * Returns the most recently stored event.
     *
     * @return StoredEvent The last stored event
     * @throws EventNotFoundException When no events exist in the store
     */
    public function lastEvent(): StoredEvent;
}
