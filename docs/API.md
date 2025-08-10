# EventStore API Documentation

## Overview

The EventStore package provides a comprehensive solution for implementing Event Sourcing patterns in PHP applications. It offers multiple storage implementations, performance monitoring, and advanced features like event versioning and filtering.

## Core Interfaces

### StoreInterface

The main interface that defines the contract for event storage implementations.

```php
interface StoreInterface
{
    public function append(Event $event): void;
    public function allStoredEventsSince(\DateTimeImmutable|int $past): array;
    public function lastEvent(): StoredEvent;
    public function getEventsByType(string $eventType, \DateTimeImmutable|int|null $since = null): array;
}
```

## Storage Implementations

### 1. StoreEventInMemory

In-memory storage implementation, ideal for testing and development.

```php
use Phariscope\EventStore\Persistence\StoreEventInMemory;

$store = new StoreEventInMemory();

// Store an event
$event = new UserRegistered('user-123', 'john@example.com');
$store->append($event);

// Retrieve last 10 events
$recentEvents = $store->allStoredEventsSince(10);

// Get events by type
$userEvents = $store->getEventsByType(UserRegistered::class);
```

### 2. StoreEventInDatabase

Persistent database storage using PDO.

```php
use Phariscope\EventStore\Persistence\StoreEventInDatabase;

$pdo = new PDO('mysql:host=localhost;dbname=eventstore', $user, $pass);
$store = new StoreEventInDatabase($pdo, 'events_table');

$store->append($event);
```

### 3. StoreEventWithMetrics

Decorator that adds performance monitoring to any store implementation.

```php
use Phariscope\EventStore\Persistence\StoreEventWithMetrics;
use Phariscope\EventStore\Performance\EventStoreMetrics;

$baseStore = new StoreEventInMemory();
$metrics = new EventStoreMetrics();
$store = new StoreEventWithMetrics($baseStore, $metrics);

// All operations are automatically tracked
$store->append($event);

// Get performance report
$report = $store->getPerformanceReport();
```

## Advanced Features

### Event Versioning

Support for evolving event schemas over time.

```php
use Phariscope\EventStore\VersionedEvent;

class UserRegisteredV2 extends VersionedEvent
{
    private string $userId;
    private string $email;
    private string $fullName;

    public function __construct(string $userId, string $email, string $fullName)
    {
        parent::__construct(2); // Version 2
        $this->userId = $userId;
        $this->email = $email;
        $this->fullName = $fullName;
    }

    public function migrateToVersion(int $targetVersion): static
    {
        // Handle migration logic here
        return $this;
    }
}
```

### Performance Monitoring

Track detailed metrics about your event store operations.

```php
use Phariscope\EventStore\Performance\EventStoreMetrics;

$metrics = new EventStoreMetrics();

// Manual tracking
$operationId = $metrics->startOperation('bulk_insert');
// ... perform operations ...
$metrics->endOperation($operationId, 100); // 100 events processed

// Get comprehensive report
$report = $metrics->getPerformanceReport();
echo $metrics->exportToJson();
```

## Event Filtering

### By Type

Filter events by their class name:

```php
// Get all UserRegistered events
$userRegistrations = $store->getEventsByType(UserRegistered::class);

// Get UserRegistered events from the last hour
$since = new DateTimeImmutable('-1 hour');
$recentRegistrations = $store->getEventsByType(UserRegistered::class, $since);

// Get last 5 UserRegistered events
$lastFiveRegistrations = $store->getEventsByType(UserRegistered::class, 5);
```

### By Time Range

Retrieve events within specific time periods:

```php
// Events from specific date
$since = new DateTimeImmutable('2023-01-01');
$events = $store->allStoredEventsSince($since);

// Last N events
$lastTenEvents = $store->allStoredEventsSince(10);
```

## Error Handling

The EventStore package provides specific exceptions for different error conditions:

```php
use Phariscope\EventStore\Exceptions\EventNotFoundException;

try {
    $lastEvent = $store->lastEvent();
} catch (EventNotFoundException $e) {
    // Handle case when no events exist
    echo $e->getMessage(); // "No events found in the store"
}

try {
    $events = $store->allStoredEventsSince(-5);
} catch (InvalidArgumentException $e) {
    // Handle invalid parameters
    echo $e->getMessage(); // "Past parameter must be a positive integer when using int type"
}
```

## Best Practices

### 1. Event Design

```php
class OrderPlaced extends Event
{
    private string $orderId;
    private string $customerId;
    private float $amount;

    public function __construct(string $orderId, string $customerId, float $amount)
    {
        parent::__construct(new DateTimeImmutable());
        $this->orderId = $orderId;
        $this->customerId = $customerId;
        $this->amount = $amount;
    }

    // Getters...
}
```

### 2. Store Configuration

```php
// For production: use database store with metrics
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$baseStore = new StoreEventInDatabase($pdo);
$store = new StoreEventWithMetrics($baseStore);

// For testing: use in-memory store
$store = new StoreEventInMemory();
```

### 3. Performance Monitoring

```php
// Monitor critical operations
$operationId = $metrics->startOperation('event_replay');
foreach ($events as $event) {
    $this->eventHandler->handle($event);
}
$metrics->endOperation($operationId, count($events));

// Regular performance reporting
if ($metrics->getThroughput('append') < 1000) {
    // Alert: low throughput detected
}
```

## Migration Guide

### From v1.0 to v2.0

1. **New Interface Method**: Implement `getEventsByType()` in custom stores
2. **Enhanced Validation**: Update error handling for new validation rules
3. **Performance Metrics**: Consider adding metrics to production stores

```php
// Before
class MyCustomStore implements StoreInterface
{
    // Only had append(), allStoredEventsSince(), lastEvent()
}

// After
class MyCustomStore implements StoreInterface
{
    // Must also implement getEventsByType()
    public function getEventsByType(string $eventType, \DateTimeImmutable|int|null $since = null): array
    {
        // Implementation here
    }
}
```

## Configuration Examples

### Development

```php
$store = new StoreEventInMemory();
```

### Production

```php
$pdo = new PDO('mysql:host=db;dbname=events', $user, $pass);
$baseStore = new StoreEventInDatabase($pdo);
$store = new StoreEventWithMetrics($baseStore);
```

### Testing

```php
class EventStoreTest extends TestCase
{
    private StoreInterface $store;

    protected function setUp(): void
    {
        $this->store = new StoreEventInMemory();
    }
}
```
