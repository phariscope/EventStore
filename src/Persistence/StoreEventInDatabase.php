<?php

namespace Phariscope\EventStore\Persistence;

use Phariscope\Event\Psr14\Event;
use Phariscope\EventStore\Exceptions\EventNotFoundException;
use Phariscope\EventStore\StoredEvent;
use Phariscope\EventStore\StoreInterface;

/**
 * Database implementation of the EventStore.
 *
 * This implementation provides persistent storage using PDO for database connectivity.
 * It's designed to work with MySQL, PostgreSQL, or SQLite databases.
 *
 * @example
 * ```php
 * $pdo = new PDO('mysql:host=localhost;dbname=eventstore', $user, $pass);
 * $store = new StoreEventInDatabase($pdo);
 * $store->append(new MyDomainEvent('data'));
 * ```
 */
class StoreEventInDatabase implements StoreInterface
{
    private \PDO $pdo;
    private string $tableName;

    public function __construct(\PDO $pdo, string $tableName = 'stored_events')
    {
        $this->pdo = $pdo;
        $this->tableName = $tableName;
        $this->ensureTableExists();
    }

    public function append(Event $event): void
    {
        $storedEvent = new StoredEvent($event);

        $sql = "INSERT INTO {$this->tableName} (event_id, event_body, type_name, occurred_on) 
                VALUES (:event_id, :event_body, :type_name, :occurred_on)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'event_id' => $storedEvent->eventId(),
            'event_body' => $storedEvent->getEventBody(),
            'type_name' => $storedEvent->typeName(),
            'occurred_on' => $storedEvent->occurredOn()->format('Y-m-d H:i:s.u')
        ]);
    }

    /**
     * @return array<int,StoredEvent>
     */
    public function allStoredEventsSince(\DateTimeImmutable|int $past): array
    {
        if (is_int($past) && $past < 0) {
            throw new \InvalidArgumentException('Past parameter must be a positive integer when using int type');
        }

        if (is_int($past)) {
            return $this->getLastNEvents($past);
        }

        return $this->getEventsSinceDate($past);
    }

    public function lastEvent(): StoredEvent
    {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY event_id DESC LIMIT 1";
        $stmt = $this->pdo->query($sql);

        if ($stmt === false) {
            throw new \RuntimeException('Failed to execute query');
        }

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new EventNotFoundException('No events found in the database store');
        }

        /** @var array{event_id: string|int, event_body: string, type_name: string, occurred_on: string} $row */
        return $this->createStoredEventFromRow($row);
    }

        /**
     * @return array<int,StoredEvent>
     */
    private function getLastNEvents(int $count): array
    {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY event_id DESC LIMIT :count";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':count', $count, \PDO::PARAM_INT);
        $stmt->execute();

        $events = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            if (is_array($row)) {
                /** @var array{event_id: string|int, event_body: string, type_name: string, occurred_on: string} $row */
                $events[] = $this->createStoredEventFromRow($row);
            }
        }

        return array_reverse($events); // Return in chronological order
    }

        /**
     * @return array<int,StoredEvent>
     */
    private function getEventsSinceDate(\DateTimeImmutable $since): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE occurred_on >= :since ORDER BY event_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['since' => $since->format('Y-m-d H:i:s.u')]);

        $events = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            if (is_array($row)) {
                /** @var array{event_id: string|int, event_body: string, type_name: string, occurred_on: string} $row */
                $events[] = $this->createStoredEventFromRow($row);
            }
        }

        return $events;
    }

    /**
     * @param array{event_id: string|int, event_body: string, type_name: string, occurred_on: string} $row
     */
    private function createStoredEventFromRow(array $row): StoredEvent
    {
        // Create a mock event to reconstruct the StoredEvent
        $occurredOn = new \DateTimeImmutable((string)$row['occurred_on']);
        $mockEvent = new class ($occurredOn) extends Event {
            public function __construct(\DateTimeImmutable $occurredOn)
            {
                parent::__construct($occurredOn);
            }
        };

        $eventId = is_int($row['event_id']) ? $row['event_id'] : (int)$row['event_id'];
        $storedEvent = new StoredEvent($mockEvent, $eventId);

        // Use reflection to set the private properties
        $reflection = new \ReflectionClass($storedEvent);

        $eventBodyProperty = $reflection->getProperty('eventBody');
        $eventBodyProperty->setAccessible(true);
        $eventBodyProperty->setValue($storedEvent, $row['event_body']);

        $typeNameProperty = $reflection->getProperty('typeName');
        $typeNameProperty->setAccessible(true);
        $typeNameProperty->setValue($storedEvent, $row['type_name']);

        return $storedEvent;
    }

    /**
     * @return array<int,StoredEvent>
     */
    public function getEventsByType(string $eventType, \DateTimeImmutable|int|null $since = null): array
    {
        if (empty($eventType)) {
            throw new \InvalidArgumentException('Event type cannot be empty');
        }

        if ($since === null) {
            return $this->getEventsByTypeOnly($eventType);
        }

        if (is_int($since)) {
            if ($since < 0) {
                throw new \InvalidArgumentException('Since parameter must be a positive integer when using int type');
            }
            return $this->getLastNEventsByType($eventType, $since);
        }

        return $this->getEventsByTypeSinceDate($eventType, $since);
    }

        /**
     * @return array<int,StoredEvent>
     */
    private function getEventsByTypeOnly(string $eventType): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE type_name = :type_name ORDER BY event_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['type_name' => $eventType]);

        $events = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            if (is_array($row)) {
                /** @var array{event_id: string|int, event_body: string, type_name: string, occurred_on: string} $row */
                $events[] = $this->createStoredEventFromRow($row);
            }
        }

        return $events;
    }

        /**
     * @return array<int,StoredEvent>
     */
    private function getLastNEventsByType(string $eventType, int $count): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE type_name = :type_name ORDER BY event_id DESC LIMIT :count";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':type_name', $eventType);
        $stmt->bindValue(':count', $count, \PDO::PARAM_INT);
        $stmt->execute();

        $events = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            if (is_array($row)) {
                /** @var array{event_id: string|int, event_body: string, type_name: string, occurred_on: string} $row */
                $events[] = $this->createStoredEventFromRow($row);
            }
        }

        return array_reverse($events); // Return in chronological order
    }

        /**
     * @return array<int,StoredEvent>
     */
    private function getEventsByTypeSinceDate(string $eventType, \DateTimeImmutable $since): array
    {
        $sql = "SELECT * FROM {$this->tableName} " .
               "WHERE type_name = :type_name AND occurred_on >= :since " .
               "ORDER BY event_id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'type_name' => $eventType,
            'since' => $since->format('Y-m-d H:i:s.u')
        ]);

        $events = [];
        while (($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
            if (is_array($row)) {
                /** @var array{event_id: string|int, event_body: string, type_name: string, occurred_on: string} $row */
                $events[] = $this->createStoredEventFromRow($row);
            }
        }

        return $events;
    }

    private function ensureTableExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
            event_id INTEGER PRIMARY KEY,
            event_body TEXT NOT NULL,
            type_name VARCHAR(255) NOT NULL,
            occurred_on DATETIME(6) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $this->pdo->exec($sql);
    }

    /**
     * Get database connection for advanced usage.
     */
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    /**
     * Get table name used for storage.
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }
}
