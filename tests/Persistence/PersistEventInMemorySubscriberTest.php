<?php

namespace Phariscope\EventStore\Tests\Persistence;

use Phariscope\EventStore\Persistence\PersistEventInMemorySubscriber;
use Phariscope\EventStore\Persistence\StoreEventInMemory;
use PHPUnit\Framework\TestCase;

class PersistEventInMemorySubscriberTest extends TestCase
{
    public function testCreate(): void
    {
        $store = new StoreEventInMemory();
        $persist = new PersistEventInMemorySubscriber($store);
        $this->assertEquals($persist->getStore(), $store);
    }
}
