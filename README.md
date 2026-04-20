# Installation

```console
composer require phariscope/event-store
```

## Supported versions

| | Supported |
| --- | --- |
| **PHP** | `>=8.1` (Symfony 8 requires PHP `>=8.4` on the application side; Composer will resolve accordingly) |
| **Symfony** (`config`, `dependency-injection`, `http-kernel`, `serializer`, `yaml`) | 6.4 LTS, 7.x, and 8.x per `composer.json` |
| **phariscope/event** | `>=1.2` (1.2.x is the reference line used in CI) |

Continuous integration runs PHPUnit against Symfony **6.4**, **7.4**, and **8.0** lines (see [`.github/workflows/ci.yml`](.github/workflows/ci.yml)).

# Usage

There is no direct usage for this package. You should use this package only if you want to develop your own event storage component.

To develop your own storage:

1. Create your own Store implementing the StoreInterface.
2. Create your subscriber by extending PersistEventSubscriberAbstract, which can be constructed with your store.

Multiple implementations of the StoreInterface are provided:

- **StoreEventInMemory**: In-memory storage for testing and development
- **StoreEventInDatabase**: Persistent database storage using PDO  
- **StoreEventWithMetrics**: Decorator adding performance monitoring to any store

### Using the SQLite persistence listener

A ready-to-use listener is provided to persist every event into a SQLite database (or any PDO-supported database) using `StoreEventInDatabase` under the hood.

Prerequisites:
- PHP with `pdo_sqlite` extension enabled (or another PDO driver if you use a different DBMS)

Example:

```php
use Phariscope\EventStore\Persistence\PersistEventInDatabaseSubscriber;

// 1) Create a PDO connection (SQLite examples)
$pdo = new PDO('sqlite:/absolute/path/to/events.sqlite');
// or in-memory for tests/dev: new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 2) Create the persistence listener (table auto-created if missing)
$persist = new PersistEventInDatabaseSubscriber($pdo, 'stored_events');

// 3) Use it as a PSR-14 listener/subscriber in your event system
// Registration depends on your dispatcher implementation.
// You can also invoke it directly:
// $persist->handle($yourEvent);
EventPublisher::instance()->subscribe($persist);

// 4) Access the underlying store when needed
$store = $persist->getStore();

// Fetch the last 10 stored events
$lastTen = $store->allStoredEventsSince(10);

// Fetch all events of a given type (optionally since a datetime or last N)
// $eventsByType = $store->getEventsByType(YourEvent::class);
```

Notes:
- The table is created automatically with the name you provide (default: `stored_events`).
- SQLite DSN formats:
  - `sqlite::memory:` for in-memory
  - `sqlite:/absolute/path/to/file.sqlite` for file-backed

### YAML configuration

You can configure the SQLite path and table name via a YAML file and build the listener from it.

Example `config/event_store.yaml`:

```yaml
event_store:
  dsn: "sqlite:///absolute/path/to/events.sqlite"  # or ":memory:" for in-memory
  table_name: "stored_events"                      # optional (default: stored_events)
```

Bootstrap from configuration:

```php
use Phariscope\EventStore\Config\EventStoreConfiguration;
use Phariscope\Event\Psr14\EventPublisher;

$config = EventStoreConfiguration::fromFile(__DIR__ . '/config/event_store.yaml');
$subscriber = $config->createSubscriber();

EventPublisher::instance()->subscribe($subscriber);
```

### Symfony Bundle integration

If you are using Symfony, enable the bundle and configure it under `config/packages/event_store.yaml`:

1) Register the bundle (Symfony Flex may do this automatically):
```php
// config/bundles.php
return [
    // ...
    Phariscope\EventStore\Bridge\Symfony\EventStoreBundle::class => ['all' => true],
];
```

2) Configure the package:
```yaml
# config/packages/event_store.yaml
event_store:
  dsn: "sqlite:///absolute/path/to/events.sqlite"  # or ":memory:"
  table_name: "stored_events"                      # optional
```

Services exposed:
- `phariscope_event_store.pdo`: configured `PDO` instance
- `phariscope_event_store.subscriber`: `PersistEventInDatabaseSubscriber`

Additional features include:
- **Event versioning** with VersionedEvent for schema evolution
- **Performance metrics** tracking with EventStoreMetrics
- **Event filtering** by type and time ranges
- **Comprehensive API documentation** in docs/API.md

# To Contribute to phariscope/Event

## Requirements

* docker
* git

## Install

* git clone git@github.com:phariscope/EventStore.git

## Unit test

```console
bin/phpunit
```

Using Test-Driven Development (TDD) principles (thanks to Kent Beck and others), following good practices (thanks to Uncle Bob and others) and the great book 'DDD in PHP' by C. Buenosvinos, C. Soronellas, K. Akbary

## Quality

* phpcs PSR12
* phpstan level 9
* coverage 100%
* infection MSI 100%

Quick check with:
```console
./codecheck
```

Check coverage with:
```console
bin/phpunit --coverage-html var
```
and view 'var/index.html' with your browser

Check infection with:
```console
bin/infection
```
and view 'var/infection.html' with your browser