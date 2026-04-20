<?php

namespace Phariscope\EventStore\Tests;

use Phariscope\EventStore\StoredEvent;
use Phariscope\EventStore\Tests\Persistence\EventSent;
use Phariscope\EventStore\Tests\Fixtures\ComplexEvent;
use Phariscope\EventStore\Tests\Fixtures\ProblematicEvent;
use Phariscope\EventStore\Tests\Fixtures\EmptyEvent;
use PHPUnit\Framework\TestCase;
use Safe\DateTimeImmutable;

class StoredEventTest extends TestCase
{
    public function testCreateStoredEvent(): void
    {
        $date = DateTimeImmutable::createFromFormat(
            "Y-m-d H:i:s",
            "2023-09-25 11:24:56",
            new \DateTimeZone('Europe/Paris')
        );

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

    public function testCreateStoredEventWithInvalidId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event ID must be a positive integer');

        $event = new EventSent("invalidId");
        new StoredEvent($event, -1);
    }

    public function testCreateStoredEventWithZeroId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event ID must be a positive integer');

        $event = new EventSent("zeroId");
        new StoredEvent($event, 0);
    }

    public function testStoredEventProperties(): void
    {
        $event = new EventSent("testId");
        $storedEvent = new StoredEvent($event, 42);

        $this->assertEquals(42, $storedEvent->eventId());
        $this->assertEquals("Phariscope\EventStore\Tests\Persistence\EventSent", $storedEvent->typeName());
        $this->assertStringContainsString('testId', $storedEvent->getEventBody());
        $this->assertJson($storedEvent->getEventBody());
    }

    public function testSerializationWithComplexEvent(): void
    {
        $complexEvent = new ComplexEvent([
            'nested' => ['data' => 'value'],
            'array' => [1, 2, 3],
            'string' => 'test'
        ]);

        $storedEvent = new StoredEvent($complexEvent, 1);

        $body = $storedEvent->getEventBody();
        $this->assertJson($body);

        $decoded = json_decode($body, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('data', $decoded);
        $this->assertArrayHasKey('occurredOn', $decoded);
    }

    public function testSerializationErrorHandling(): void
    {
        // Create an event that will cause serialization issues
        $problematicEvent = new ProblematicEvent();

        // This should throw a RuntimeException with our custom message
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to serialize event:');

        new StoredEvent($problematicEvent, 1);
    }

    public function testEmptySerializationHandling(): void
    {
        $emptyEvent = new EmptyEvent();

        try {
            $storedEvent = new StoredEvent($emptyEvent, 1);
            $body = $storedEvent->getEventBody();

            // Should not be empty
            $this->assertNotEmpty($body);
            $this->assertJson($body);
        } catch (\RuntimeException $e) {
            // If serialization fails, should get a meaningful error
            $this->assertStringContainsString('Failed to serialize event', $e->getMessage());
        }
    }
}
