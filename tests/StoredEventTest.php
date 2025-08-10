<?php

namespace Phariscope\EventStore\Tests;

use Phariscope\EventStore\StoredEvent;
use Phariscope\EventStore\Tests\Persistence\EventSent;
use PHPUnit\Framework\TestCase;
use Safe\DateTimeImmutable;

class StoredEventTest extends TestCase
{
    public function testCreateStoredEvent(): void
    {
        $date = DateTimeImmutable::createFromFormat("Y-m-d H:i:s", "2023-09-25 11:24:56");

        $event = new EventSent("aId", $date->getInnerDateTime());
        $storedEvent = new StoredEvent($event, 1);
        $this->assertEquals(1, $storedEvent->eventId());
        $this->assertEquals("Phariscope\EventStore\Tests\Persistence\EventSent", $storedEvent->typeName());
        $this->assertEquals(
            '{"id":"aId","occurredOn":"2023-09-25T11:24:56+02:00"}',
            $storedEvent->getEventBody()
        );
    }

    public function testCreateStoredEventWithAutoId(): void
    {
        $event = new EventSent("autoId");
        $storedEvent1 = new StoredEvent($event);
        $storedEvent2 = new StoredEvent($event);

        $this->assertTrue($storedEvent1->eventId() > 0);
        $this->assertTrue($storedEvent2->eventId() > $storedEvent1->eventId());
    }
}
