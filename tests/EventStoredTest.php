<?php

namespace Phariscope\EventStore\Tests;

use Phariscope\EventStore\EventStored;
use Phariscope\EventStore\Tests\Persistence\EventSent;
use PHPUnit\Framework\TestCase;
use Safe\DateTimeImmutable;

class EventStoredTest extends TestCase
{
    public function testCreateStoredEvent(): void
    {
        $event = new EventSent("aId", DateTimeImmutable::createFromFormat("Y-m-d H:i:s", "2023-09-25 11:24:56"));
        $storedEvent = new EventStored($event, 1);
        $this->assertEquals(1, $storedEvent->eventId());
        $this->assertEquals("Phariscope\EventStore\Tests\Persistence\EventSent", $storedEvent->typeName());
        $this->assertEquals(
            '{"id":"aId","occurredOn":{"innerDateTime":"2023-09-25T11:24:56+02:00"}}',
            $storedEvent->getEventBody()
        );
    }
}
