<?php

namespace Phariscope\EventStore\Tests\Persistence;

use Phariscope\Event\Psr14\Event;

/**
 * EventSent : nom + verbe au passé pour nommer vos événements
 */
class EventSent extends Event
{
    private string $id;

    public function __construct(string $id, \DateTimeImmutable $occurredOn = new \DateTimeImmutable())
    {
        parent::__construct($occurredOn);
        $this->id = $id;
    }

    public function id(): string
    {
        return $this->id;
    }
}
