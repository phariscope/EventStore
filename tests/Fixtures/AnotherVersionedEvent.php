<?php

namespace Phariscope\EventStore\Tests\Fixtures;

use Phariscope\EventStore\VersionedEvent;

class AnotherVersionedEvent extends VersionedEvent
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
}
