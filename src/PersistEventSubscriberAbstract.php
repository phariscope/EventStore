<?php

namespace Phariscope\EventStore;

use Phariscope\Event\Psr14\Event;
use Phariscope\Event\Psr14\ListenerInterface;

abstract class PersistEventSubscriberAbstract implements ListenerInterface
{
    private StoreInterface $store;

    public function __construct(StoreInterface $store)
    {
        $this->store = $store;
    }

    public function handle(Event $event): bool
    {
        $this->store->append($event);
        return true;
    }

    public function isSubscribedTo(Event $event): bool
    {
        return true;
    }

    public function getStore(): StoreInterface
    {
        return $this->store;
    }
}
