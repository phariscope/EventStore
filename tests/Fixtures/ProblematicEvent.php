<?php

namespace Phariscope\EventStore\Tests\Fixtures;

use Phariscope\Event\Psr14\Event;

class ProblematicEvent extends Event
{
    /** @var resource|false */
    private $resource;

    public function __construct()
    {
        parent::__construct(new \DateTimeImmutable());
        // Create a resource that can't be serialized
        $this->resource = fopen('php://memory', 'r');
    }

    /** @return resource|false */
    public function getResource()
    {
        return $this->resource;
    }

    public function __destruct()
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }
}
