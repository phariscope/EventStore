<?php

namespace Phariscope\EventStore\Tests\Persistence;

use Phariscope\EventStore\Exceptions\EventNotFoundException;
use Phariscope\EventStore\Persistence\StoreEventInMemory;
use PHPUnit\Framework\TestCase;

class StoreEventInMemoryTest extends TestCase
{
    public function testAllStoredEventsSinceDate(): void
    {
        $debut = new \DateTimeImmutable();
        $store = new StoreEventInMemory();
        for ($i = 1; $i <= 15; $i++) {
            $event = new EventSent("unEvenementAPublier" . $i);
            $store->append($event);
        }
        $events = $store->allStoredEventsSince($debut);

        $this->assertEquals(15, count($events));
        $this->assertStringContainsString('unEvenementAPublier1', $events[0]->getEventBody());
        $this->assertStringContainsString('unEvenementAPublier2', $events[1]->getEventBody());
        $this->assertStringContainsString('unEvenementAPublier15', $events[14]->getEventBody());
    }

    public function testAllStoredEventsSinceInt(): void
    {
        $store = new StoreEventInMemory();
        for ($i = 1; $i <= 15; $i++) {
            $event = new EventSent("unEvenementAPublier" . $i);
            $store->append($event);
        }
        $events = $store->allStoredEventsSince(5);
        $this->assertEquals(5, count($events));
        $this->assertStringContainsString('unEvenementAPublier11', $events[0]->getEventBody());
        $this->assertStringContainsString('unEvenementAPublier12', $events[1]->getEventBody());
        $this->assertStringContainsString('unEvenementAPublier13', $events[2]->getEventBody());
        $this->assertStringContainsString('unEvenementAPublier14', $events[3]->getEventBody());
        $this->assertStringContainsString('unEvenementAPublier15', $events[4]->getEventBody());
    }

    public function testAllStoredEventsSinceOutsideLimits(): void
    {
        $store = new StoreEventInMemory();
        for ($i = 1; $i <= 2; $i++) {
            $event = new EventSent("unEvenementAPublier" . $i);
            $store->append($event);
        }
        $events = $store->allStoredEventsSince(3);
        $this->assertEquals(2, count($events));
        $this->assertStringContainsString('unEvenementAPublier1', $events[0]->getEventBody());
        $this->assertStringContainsString('unEvenementAPublier2', $events[1]->getEventBody());

        $events = $store->allStoredEventsSince(5);
        $this->assertEquals(2, count($events));
    }

    public function testLastEvent(): void
    {
        $event = new EventSent("dernier");
        $store = new StoreEventInMemory();
        $store->append($event);
        $se = $store->lastEvent();

        $this->assertStringContainsString('dernier', $se->getEventBody());
    }

    public function testLastEventNoEventException(): void
    {
        $this->expectException(EventNotFoundException::class);
        $store = new StoreEventInMemory();
        $se = $store->lastEvent();
    }

    public function testEventIdsAreIncremental(): void
    {
        $store = new StoreEventInMemory();
        $event1 = new EventSent("premier");
        $event2 = new EventSent("second");

        $store->append($event1);
        $store->append($event2);

        $events = $store->allStoredEventsSince(2);
        $this->assertEquals(2, count($events));
        $this->assertTrue($events[1]->eventId() > $events[0]->eventId());
    }

    public function testValidationWithNegativeInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Past parameter must be a positive integer when using int type');

        $store = new StoreEventInMemory();
        $store->allStoredEventsSince(-5);
    }

    public function testComplexEventStorageScenario(): void
    {
        $store = new StoreEventInMemory();
        $baseTime = new \DateTimeImmutable('2023-01-01 10:00:00');

        // Store events at different times
        $event1 = new EventSent("first", $baseTime);
        $event2 = new EventSent("second", $baseTime->modify('+1 hour'));
        $event3 = new EventSent("third", $baseTime->modify('+2 hours'));

        $store->append($event1);
        $store->append($event2);
        $store->append($event3);

        // Test time-based retrieval
        $eventsAfterFirstHour = $store->allStoredEventsSince($baseTime->modify('+30 minutes'));
        $this->assertEquals(2, count($eventsAfterFirstHour));
        $this->assertStringContainsString('second', $eventsAfterFirstHour[0]->getEventBody());
        $this->assertStringContainsString('third', $eventsAfterFirstHour[1]->getEventBody());

        // Test count-based retrieval
        $lastTwoEvents = $store->allStoredEventsSince(2);
        $this->assertEquals(2, count($lastTwoEvents));
        $this->assertStringContainsString('second', $lastTwoEvents[0]->getEventBody());
        $this->assertStringContainsString('third', $lastTwoEvents[1]->getEventBody());

        // Test last event
        $lastEvent = $store->lastEvent();
        $this->assertStringContainsString('third', $lastEvent->getEventBody());
    }
}
