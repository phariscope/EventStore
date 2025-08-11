<?php

namespace Phariscope\EventStore\Tests\Fixtures;

use Phariscope\EventStore\VersionedEvent;

class MigratableVersionedEvent extends VersionedEvent
{
    private string $data;

    public function __construct(int $version, string $data, ?\DateTimeImmutable $occurredOn = null)
    {
        parent::__construct($version, $occurredOn);
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function migrateToVersion(int $targetVersion): static
    {
        if ($targetVersion <= $this->getVersion()) {
            throw new \InvalidArgumentException('Target version must be higher than current version');
        }

        $newData = $this->data;
        $currentVersion = $this->getVersion();

        // Simulate migration steps
        for ($v = $currentVersion + 1; $v <= $targetVersion; $v++) {
            if ($v == 2) {
                $newData .= '_migrated';
            } elseif ($v == 3) {
                $newData .= '_v3';
            }
        }

        /** @var static */
        return new self($targetVersion, $newData, $this->occurredOn());
    }
}
