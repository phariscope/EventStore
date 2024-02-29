<?php

namespace Phariscope\EventStore\Tests\Persistence;

use Phariscope\Event\Psr14\Event;

/**
 * EventSended : nom + verbe au passé pour nommer vos evennements
 */
class EventSent extends Event
{
    private string $id;

    public function __construct(string $id, \DateTimeImmutable $occuredOn = new \DateTimeImmutable())
    {
        parent::__construct($occuredOn);
        $this->id = $id;
    }

    public function id(): string
    {
        return $this->id;
    }
}
