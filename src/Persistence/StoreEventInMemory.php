<?php

namespace Phariscope\EventStore\Persistence;

use Phariscope\Event\Psr14\Event;
use Phariscope\EventStore\Exceptions\EventNotFoundException;
use Phariscope\EventStore\StoredEvent;
use Phariscope\EventStore\StoreInterface;

class StoreEventInMemory implements StoreInterface
{
    /** @var array<int,StoredEvent> $storedEvents */
    private array $storedEvents = [];
    private int $nextId = 1;

    public function append(Event $event): void
    {
        $storedEvent = new StoredEvent(
            $event,
            $this->nextId++
        );
        $this->storedEvents[$storedEvent->eventId()] = $storedEvent;
    }

    /**
     * @return array<int,StoredEvent>
     */
    public function allStoredEventsSince(\DateTimeImmutable|int $past): array
    {
        if (is_int($past) && $past < 0) {
            throw new \InvalidArgumentException('Past parameter must be a positive integer when using int type');
        }

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
