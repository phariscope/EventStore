<?php

namespace Phariscope\EventStore;

use Phariscope\Event\Psr14\Event;

/**
 * Represents a versioned domain event.
 *
 * This class extends the base Event to add version support, allowing for
 * event schema evolution and backward compatibility.
 *
 * @example
 * ```php
 * class UserRegisteredV2 extends VersionedEvent
 * {
 *     public function __construct(string $userId, string $email, string $name)
 *     {
 *         parent::__construct(2); // Version 2 of UserRegistered
 *         $this->userId = $userId;
 *         $this->email = $email;
 *         $this->name = $name;
 *     }
 * }
 * ```
 */
abstract class VersionedEvent extends Event
{
    private int $version;

    public function __construct(int $version, ?\DateTimeImmutable $occurredOn = null)
    {
        if ($version <= 0) {
            throw new \InvalidArgumentException('Event version must be a positive integer');
        }

        parent::__construct($occurredOn ?? new \DateTimeImmutable());
        $this->version = $version;
    }

    /**
     * Get the version of this event.
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * Get a unique identifier combining class name and version.
     */
    public function getVersionedTypeName(): string
    {
        return get_class($this) . '_v' . $this->version;
    }

    /**
     * Check if this event is compatible with a specific version.
     */
    public function isCompatibleWith(int $version): bool
    {
        return $this->version >= $version;
    }

    /**
     * Migrate this event to a newer version.
     * Subclasses should override this method to handle version migrations.
     */
    public function migrateToVersion(int $targetVersion): static
    {
        if ($targetVersion <= $this->version) {
            throw new \InvalidArgumentException('Target version must be higher than current version');
        }

        // Default implementation: return self (no migration needed)
        return $this;
    }
}
