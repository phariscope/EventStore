<?php

namespace Phariscope\EventStore\Persistence;

use Phariscope\EventStore\PersistEventSubscriberAbstract;

class PersistEventInMemorySubscriber extends PersistEventSubscriberAbstract
{
    public function __construct(StoreEventInMemory $store = new StoreEventInMemory())
    {
        parent::__construct($store);
    }
}
