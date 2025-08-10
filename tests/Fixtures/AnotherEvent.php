<?php

namespace Phariscope\EventStore\Tests\Fixtures;

use Phariscope\Event\Psr14\Event;

class AnotherEvent extends Event
{
    private string $data;

    public function __construct(string $data)
    {
        parent::__construct(new \DateTimeImmutable());
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }
}
