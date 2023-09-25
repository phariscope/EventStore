<?php

namespace Phariscope\EventStore;

use Phariscope\Event\EventAbstract;
use Phariscope\Event\EventSubscriber;

abstract class PersistEventSubscriberAbstract implements EventSubscriber
{
    private StoreInterface $store;

    public function __construct(StoreInterface $store)
    {
        $this->store = $store;
    }

    public function handle(EventAbstract $event): bool
    {
        $this->store->append($event);
        return true;
    }

    public function isSubscribedTo(EventAbstract $event): bool
    {
        return true;
    }
}
