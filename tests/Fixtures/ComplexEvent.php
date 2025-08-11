<?php

namespace Phariscope\EventStore\Tests\Fixtures;

use Phariscope\Event\Psr14\Event;

class ComplexEvent extends Event
{
    /** @var array<string, mixed> */
    private array $data;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        parent::__construct(new \DateTimeImmutable());
        $this->data = $data;
    }

    /** @return array<string, mixed> */
    public function getData(): array
    {
        return $this->data;
    }
}
