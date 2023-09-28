<?php

namespace Phariscope\EventStore\Persistence;

use Phariscope\Event\EventAbstract;
use Phariscope\EventStore\Exceptions\EventNotFoundException;
use Phariscope\EventStore\StoredEvent;
use Phariscope\EventStore\StoreInterface;

class StoreEventInMemory implements StoreInterface
{
    /** @var array<int,StoredEvent> $storedEvents */
    private array $storedEvents = [];

    public function append(EventAbstract $event): void
    {
        /** @var int $id */
        $id = hexdec(uniqid()); // l'id est unique et plus grand que tous les id ayant été générés auparavant
        $storedEvent = new StoredEvent(
            $event,
            $id
        );
        $this->storedEvents[$storedEvent->eventId()] = $storedEvent;
    }

    /**
     * @return array<int,StoredEvent>
     */
    public function allStoredEventsSince(\DateTimeImmutable|int $past): array
    {
        $result = [];
        if (is_int($past)) {
            $offset = count($this->storedEvents) - $past;
            $offset = max(0, $offset);
            $length = min($past, count($this->storedEvents));
            $result = array_slice($this->storedEvents, $offset, $length);
        } else {
            foreach ($this->storedEvents as $stored) {
                if ($stored->occurredOn() >= $past) {
                    $result[] = $stored;
                }
            }
        }
        return $result;
    }

    public function lastEvent(): StoredEvent
    {
        $last = end($this->storedEvents) ?: throw new EventNotFoundException();

        return $last;
    }
}
