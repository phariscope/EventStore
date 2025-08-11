<?php

namespace Phariscope\EventStore\Tests\Persistence;

use Phariscope\EventStore\Persistence\StoreEventInMemory;
use Phariscope\EventStore\Tests\Fixtures\AnotherEvent;
use PHPUnit\Framework\TestCase;

class StoreEventFilteringTest extends TestCase
{
    private StoreEventInMemory $store;

    protected function setUp(): void
    {
        $this->store = new StoreEventInMemory();
    }

    public function testGetEventsByType(): void
    {
        // Add different types of events
        $event1 = new EventSent("first");
        $event2 = new EventSent("second");
        $event3 = new AnotherEvent("third");

        $this->store->append($event1);
        $this->store->append($event2);
        $this->store->append($event3);

        // Filter by EventSent type
        $sentEvents = $this->store->getEventsByType(EventSent::class);

        $this->assertEquals(2, count($sentEvents));
        $this->assertStringContainsString('first', $sentEvents[0]->getEventBody());
        $this->assertStringContainsString('second', $sentEvents[1]->getEventBody());
    }

    public function testGetEventsByTypeWithTimeFilter(): void
    {
        $baseTime = new \DateTimeImmutable('2023-01-01 10:00:00');

        $event1 = new EventSent("old", $baseTime);
        $event2 = new EventSent("new", $baseTime->modify('+2 hours'));

        $this->store->append($event1);
        $this->store->append($event2);

        // Get EventSent events since 1 hour after base time
        $recentEvents = $this->store->getEventsByType(
            EventSent::class,
            $baseTime->modify('+1 hour')
        );

        $this->assertEquals(1, count($recentEvents));
        $this->assertStringContainsString('new', $recentEvents[0]->getEventBody());
    }

    public function testGetEventsByTypeWithCountFilter(): void
    {
        // Add multiple events of same type
        for ($i = 1; $i <= 5; $i++) {
            $event = new EventSent("event{$i}");
            $this->store->append($event);
        }

        // Get last 3 events of this type
        $lastThree = $this->store->getEventsByType(EventSent::class, 3);

        $this->assertEquals(3, count($lastThree));
        $this->assertStringContainsString('event3', $lastThree[0]->getEventBody());
        $this->assertStringContainsString('event4', $lastThree[1]->getEventBody());
        $this->assertStringContainsString('event5', $lastThree[2]->getEventBody());
    }

    public function testGetEventsByTypeEmptyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event type cannot be empty');

        $this->store->getEventsByType('');
    }

    public function testGetEventsByTypeNegativeCount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Since parameter must be a positive integer when using int type');

        $this->store->getEventsByType(EventSent::class, -1);
    }

    public function testGetEventsByTypeNoMatches(): void
    {
        $event = new EventSent("test");
        $this->store->append($event);

        $events = $this->store->getEventsByType('NonExistentEventType');

        $this->assertEquals(0, count($events));
    }

    public function testGetEventsByTypeWithZeroCount(): void
    {
        $this->store->append(new EventSent("test"));

        $events = $this->store->getEventsByType(EventSent::class, 0);

        $this->assertEquals(0, count($events));
    }

    public function testGetEventsByTypeWithLargeCount(): void
    {
        // Add 3 events
        for ($i = 1; $i <= 3; $i++) {
            $this->store->append(new EventSent("event{$i}"));
        }

        // Request more than available
        $events = $this->store->getEventsByType(EventSent::class, 10);

        // Should return all available events
        $this->assertEquals(3, count($events));
    }

    public function testGetEventsByTypeWithFutureDate(): void
    {
        $this->store->append(new EventSent("past_event"));

        $futureDate = new \DateTimeImmutable('+1 hour');
        $events = $this->store->getEventsByType(EventSent::class, $futureDate);

        $this->assertEquals(0, count($events));
    }

    public function testGetEventsByTypeWithPastDate(): void
    {
        $pastDate = new \DateTimeImmutable('-1 hour');

        $this->store->append(new EventSent("recent_event"));

        $events = $this->store->getEventsByType(EventSent::class, $pastDate);

        $this->assertEquals(1, count($events));
        $this->assertStringContainsString('recent_event', $events[0]->getEventBody());
    }

    public function testGetEventsByTypeExactTimeMatch(): void
    {
        $exactTime = new \DateTimeImmutable('2023-01-01 12:00:00');

        $event1 = new EventSent("before", $exactTime->modify('-1 second'));
        $event2 = new EventSent("exact", $exactTime);
        $event3 = new EventSent("after", $exactTime->modify('+1 second'));

        $this->store->append($event1);
        $this->store->append($event2);
        $this->store->append($event3);

        $events = $this->store->getEventsByType(EventSent::class, $exactTime);

        // Should include exact time and after, but not before
        $this->assertEquals(2, count($events));
        $this->assertStringContainsString('exact', $events[0]->getEventBody());
        $this->assertStringContainsString('after', $events[1]->getEventBody());
    }

    public function testMixedEventTypesFiltering(): void
    {
        // Add different types in mixed order
        $this->store->append(new EventSent("sent1"));
        $this->store->append(new AnotherEvent("other1"));
        $this->store->append(new EventSent("sent2"));
        $this->store->append(new AnotherEvent("other2"));
        $this->store->append(new EventSent("sent3"));

        $sentEvents = $this->store->getEventsByType(EventSent::class);
        $otherEvents = $this->store->getEventsByType(AnotherEvent::class);

        $this->assertEquals(3, count($sentEvents));
        $this->assertEquals(2, count($otherEvents));

        // Verify order is preserved
        $this->assertStringContainsString('sent1', $sentEvents[0]->getEventBody());
        $this->assertStringContainsString('sent2', $sentEvents[1]->getEventBody());
        $this->assertStringContainsString('sent3', $sentEvents[2]->getEventBody());
    }

    public function testGetEventsByTypeNullSince(): void
    {
        $this->store->append(new EventSent("event1"));
        $this->store->append(new EventSent("event2"));

        $events = $this->store->getEventsByType(EventSent::class, null);

        $this->assertEquals(2, count($events));
    }
}
