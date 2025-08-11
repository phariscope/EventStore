<?php

namespace Phariscope\EventStore\Tests\Fixtures;

use Phariscope\Event\Psr14\Event;

class EmptyEvent extends Event
{
    public function __construct()
    {
        parent::__construct(new \DateTimeImmutable());
    }
}
