<?php

namespace Phariscope\EventStore\Tests\Persistence;

use Phariscope\EventStore\Exceptions\EventNotFoundException;
use Phariscope\EventStore\Persistence\StoreEventInDatabase;
use Phariscope\EventStore\Tests\Fixtures\AnotherDatabaseEvent;
use PHPUnit\Framework\TestCase;

class StoreEventInDatabaseTest extends TestCase
{
    private \PDO $pdo;
    private StoreEventInDatabase $store;

    protected function setUp(): void
    {
        // Use SQLite in-memory database for testing
        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->store = new StoreEventInDatabase($this->pdo, 'test_events');
    }

    public function testAppendAndRetrieve(): void
    {
        // Arrange
        $event = new EventSent("database_test");
        $this->store->append($event);

        // Act
        $events = $this->store->allStoredEventsSince(1);

        $this->assertEquals(1, count($events));
        $this->assertStringContainsString('database_test', $events[0]->getEventBody());
        $this->assertEquals(1, $this->store->lastEvent()->eventId());
        $this->assertEquals($this->pdo, $this->store->getPdo());
    }

    public function testGetEventsByType(): void
    {
        // Arrange
        $event1 = new EventSent("sent1");
        $event2 = new EventSent("sent2");
        $event3 = new AnotherDatabaseEvent("other");

        $this->store->append($event1);
        $this->store->append($event2);
        $this->store->append($event3);

        // Act
        $sentEvents = $this->store->getEventsByType(EventSent::class);
        $anotherEvents = $this->store->getEventsByType(AnotherDatabaseEvent::class);

        $this->assertEquals(2, count($sentEvents));
        $this->assertStringContainsString('sent1', $sentEvents[0]->getEventBody());
        $this->assertStringContainsString('sent2', $sentEvents[1]->getEventBody());
        $this->assertEquals(1, count($anotherEvents));
        $this->assertStringContainsString('other', $anotherEvents[0]->getEventBody());
    }

    public function testGetEventsByTypeWithCount(): void
    {
        // Arrange
        for ($i = 1; $i <= 5; $i++) {
            $this->store->append(new EventSent("event{$i}"));
        }

        // Act
        $lastThree = $this->store->getEventsByType(EventSent::class, 3);

        // Assert
        $this->assertEquals(3, count($lastThree));
        $this->assertStringContainsString('event3', $lastThree[0]->getEventBody());
        $this->assertStringContainsString('event4', $lastThree[1]->getEventBody());
        $this->assertStringContainsString('event5', $lastThree[2]->getEventBody());
    }

    public function testGetEventsByTypeWithDate(): void
    {
        // Arrange
        $baseTime = new \DateTimeImmutable('2023-01-01 10:00:00');

        $event1 = new EventSent("old", $baseTime);
        $event2 = new EventSent("new", $baseTime->modify('+2 hours'));

        $this->store->append($event1);
        $this->store->append($event2);

        // Act
        $recentEvents = $this->store->getEventsByType(
            EventSent::class,
            $baseTime->modify('+1 hour')
        );

        // Assert
        $this->assertEquals(1, count($recentEvents));
        $this->assertStringContainsString('new', $recentEvents[0]->getEventBody());
    }

    public function testAllStoredEventsSinceZeroReturnsEmpty(): void
    {
        // Arrange
        for ($i = 1; $i <= 3; $i++) {
            $this->store->append(new EventSent("db{$i}"));
        }

        // Act
        $events = $this->store->allStoredEventsSince(0);

        // Assert
        $this->assertCount(0, $events);
    }

    public function testGetEventsByTypeSinceDateOrderAndCount(): void
    {
        // Arrange
        $baseTime = new \DateTimeImmutable('2023-01-01 10:00:00');

        $e1 = new EventSent('a', $baseTime);
        $e2 = new EventSent('b', $baseTime->modify('+1 minute'));
        $e3 = new EventSent('c', $baseTime->modify('+2 minutes'));

        $this->store->append($e1);
        $this->store->append($e2);
        $this->store->append($e3);

        // Act
        $since = $baseTime->modify('+30 seconds');
        $events = $this->store->getEventsByType(EventSent::class, $since);

        // Assert
        // Should return e2 then e3 in ascending order by event_id
        $this->assertCount(2, $events);
        $this->assertStringContainsString('b', $events[0]->getEventBody());
        $this->assertStringContainsString('c', $events[1]->getEventBody());
    }

    public function testLastEventWhenEmpty(): void
    {
        $this->expectException(EventNotFoundException::class);
        $this->expectExceptionMessage('No events found in the database store');

        $this->store->lastEvent();
    }

    public function testEmptyTypeValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event type cannot be empty');

        $this->store->getEventsByType('');
    }

    public function testNegativeCountValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Since parameter must be a positive integer when using int type');

        $this->store->getEventsByType(EventSent::class, -1);
    }

    public function testGetTableName(): void
    {
        $tableName = $this->store->getTableName();
        $this->assertEquals('test_events', $tableName);
    }

    public function testCustomTableName(): void
    {
        $customStore = new StoreEventInDatabase($this->pdo, 'custom_events');

        $this->assertEquals('custom_events', $customStore->getTableName());

                // Verify table was created with custom name
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='custom_events'");

        // Assert
        $this->assertNotFalse($stmt);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $this->assertIsArray($result);
    }

    public function testDefaultTableName(): void
    {
        $defaultStore = new StoreEventInDatabase($this->pdo);

        $this->assertEquals('stored_events', $defaultStore->getTableName());
    }

    public function testLargeEventBody(): void
    {
        $largeData = str_repeat('x', 10000);
        $event = new EventSent($largeData);

        $this->store->append($event);

        $events = $this->store->allStoredEventsSince(1);
        $this->assertEquals(1, count($events));
        $this->assertStringContainsString($largeData, $events[0]->getEventBody());
    }

    public function testMultipleAppendsWithAutoIncrement(): void
    {
        // Arrange
        $events = [];
        for ($i = 1; $i <= 10; $i++) {
            $event = new EventSent("event{$i}");
            $this->store->append($event);
            $events[] = $event;
        }

        // Act
        $storedEvents = $this->store->allStoredEventsSince(10);

        // Assert
        $this->assertEquals(10, count($storedEvents));
        // Verify IDs are incremental
        for ($i = 1; $i < count($storedEvents); $i++) {
            $this->assertGreaterThan(
                $storedEvents[$i - 1]->eventId(),
                $storedEvents[$i]->eventId()
            );
        }
    }

    public function testAllStoredEventsSinceWithNegativeInteger(): void
    {
        // Test the uncovered negative parameter validation
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Past parameter must be a positive integer when using int type');

        $this->store->allStoredEventsSince(-1);
    }

    public function testGetEventsByTypeWithNegativeInteger(): void
    {
        // Test the uncovered negative parameter validation in getEventsByType
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Since parameter must be a positive integer when using int type');

        $this->store->getEventsByType(EventSent::class, -1);
    }

    public function testGetEventsSinceDateWithInvalidParameters(): void
    {
        // Arrange
        $event = new EventSent("test_event");
        $this->store->append($event);

        // Act
        $since = new \DateTimeImmutable('2020-01-01');
        $events = $this->store->allStoredEventsSince($since);

        // Assert
        // This should work and return events
        $this->assertNotEmpty($events);

        // Act
        // Test with future date - should return empty
        $futureDate = new \DateTimeImmutable('2030-01-01');
        $events = $this->store->allStoredEventsSince($futureDate);

        // Assert
        $this->assertEmpty($events);
    }

    public function testSerializationErrorHandling(): void
    {
        // Arrange
        $problematicEvent = new class extends \Phariscope\Event\Psr14\Event {
            public function __construct()
            {
                parent::__construct(new \DateTimeImmutable());
            }

            public function jsonSerialize(): mixed
            {
                return null; // This should trigger empty result
            }
        };

        // Act
        try {
            new \Phariscope\EventStore\StoredEvent($problematicEvent);
            $this->fail('Expected RuntimeException for empty serialization');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Failed to serialize event', $e->getMessage());
        }
    }

    public function testLastEventFailedToExecuteQuery(): void
    {
        // Arrange
        $event = new EventSent("test");
        $pdo = $this->createMock(\PDO::class);
        $pdo->method('query')->willReturn(false);
        $store = new StoreEventInDatabase($pdo);

        // Act
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to execute query');
        $store->lastEvent();
    }
}
