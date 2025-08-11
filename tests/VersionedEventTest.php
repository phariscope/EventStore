<?php

namespace Phariscope\EventStore\Tests;

use Phariscope\EventStore\VersionedEvent;
use Phariscope\EventStore\Tests\Fixtures\TestVersionedEvent;
use Phariscope\EventStore\Tests\Fixtures\AnotherVersionedEvent;
use Phariscope\EventStore\Tests\Fixtures\MigratableVersionedEvent;
use PHPUnit\Framework\TestCase;

class VersionedEventTest extends TestCase
{
    public function testCreateVersionedEvent(): void
    {
        $event = new TestVersionedEvent(2, 'test-data');

        $this->assertEquals(2, $event->getVersion());
        $this->assertEquals(
            'Phariscope\EventStore\Tests\Fixtures\TestVersionedEvent_v2',
            $event->getVersionedTypeName()
        );
        $this->assertEquals('test-data', $event->getData());
    }

    public function testInvalidVersion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event version must be a positive integer');

        new TestVersionedEvent(0, 'test');
    }

    public function testNegativeVersion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event version must be a positive integer');

        new TestVersionedEvent(-1, 'test');
    }

    public function testVersionCompatibility(): void
    {
        $eventV3 = new TestVersionedEvent(3, 'test');

        $this->assertTrue($eventV3->isCompatibleWith(1));
        $this->assertTrue($eventV3->isCompatibleWith(2));
        $this->assertTrue($eventV3->isCompatibleWith(3));
        $this->assertFalse($eventV3->isCompatibleWith(4));
    }

    public function testMigrationValidation(): void
    {
        $event = new TestVersionedEvent(2, 'test');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target version must be higher than current version');

        $event->migrateToVersion(1);
    }

    public function testDefaultMigration(): void
    {
        $event = new TestVersionedEvent(1, 'test');
        $migrated = $event->migrateToVersion(2);

        // Default implementation returns self
        $this->assertSame($event, $migrated);
    }

    public function testVersionedTypeName(): void
    {
        $event1 = new TestVersionedEvent(1, 'test');
        $event2 = new TestVersionedEvent(2, 'test');

        $this->assertNotEquals($event1->getVersionedTypeName(), $event2->getVersionedTypeName());
        $this->assertStringEndsWith('Fixtures\TestVersionedEvent_v1', $event1->getVersionedTypeName());
        $this->assertStringEndsWith('Fixtures\TestVersionedEvent_v2', $event2->getVersionedTypeName());
    }

    public function testCustomOccurredOn(): void
    {
        $customTime = new \DateTimeImmutable('2023-01-01 12:00:00');
        $event = new TestVersionedEvent(1, 'test', $customTime);

        $this->assertEquals($customTime, $event->occurredOn());
    }

    public function testVersionBoundaries(): void
    {
        // Test minimum valid version
        $event = new TestVersionedEvent(1, 'min');
        $this->assertEquals(1, $event->getVersion());

        // Test large version number
        $event = new TestVersionedEvent(999999, 'max');
        $this->assertEquals(999999, $event->getVersion());
    }

    public function testVersionCompatibilityEdgeCases(): void
    {
        $event = new TestVersionedEvent(5, 'test');

        // Same version should be compatible
        $this->assertTrue($event->isCompatibleWith(5));

        // Version 1 should always be compatible
        $this->assertTrue($event->isCompatibleWith(1));

        // Future versions should not be compatible
        $this->assertFalse($event->isCompatibleWith(6));
        $this->assertFalse($event->isCompatibleWith(100));
    }

    public function testMigrationEdgeCases(): void
    {
        $event = new TestVersionedEvent(2, 'test');

        // Same version migration should fail
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target version must be higher than current version');

        $event->migrateToVersion(2);
    }

    public function testMigrationWithLowerVersion(): void
    {
        $event = new TestVersionedEvent(5, 'test');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target version must be higher than current version');

        $event->migrateToVersion(3);
    }

    public function testVersionedTypeNameUniqueness(): void
    {
        $event1v1 = new TestVersionedEvent(1, 'test');
        $event1v2 = new TestVersionedEvent(2, 'test');
        $event2v1 = new AnotherVersionedEvent(1, 'test');

        $type1v1 = $event1v1->getVersionedTypeName();
        $type1v2 = $event1v2->getVersionedTypeName();
        $type2v1 = $event2v1->getVersionedTypeName();

        // Same class, different versions should be different
        $this->assertNotEquals($type1v1, $type1v2);

        // Different classes, same version should be different
        $this->assertNotEquals($type1v1, $type2v1);

        // All should be unique
        $this->assertNotEquals($type1v2, $type2v1);
    }

    public function testVersionedEventInheritance(): void
    {
        $event = new TestVersionedEvent(1, 'inheritance_test');

        // Should still be an Event
        $this->assertInstanceOf(\Phariscope\Event\Psr14\Event::class, $event);

        // Should have occurred on timestamp
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->occurredOn());

        // Should be recent
        $this->assertLessThan(2, time() - $event->occurredOn()->getTimestamp());
    }

    public function testSuccessfulMigration(): void
    {
        $event = new MigratableVersionedEvent(1, 'migrate_test');

        $migrated = $event->migrateToVersion(2);

        $this->assertEquals(2, $migrated->getVersion());
        $this->assertEquals('migrate_test_migrated', $migrated->getData());
        $this->assertNotSame($event, $migrated); // Should be a new instance
    }

    public function testMultipleVersionMigration(): void
    {
        $event = new MigratableVersionedEvent(1, 'multi_migrate');

        $migrated = $event->migrateToVersion(3);

        $this->assertEquals(3, $migrated->getVersion());
        // Should have applied multiple migration steps
        $this->assertEquals('multi_migrate_migrated_v3', $migrated->getData());
    }
}
