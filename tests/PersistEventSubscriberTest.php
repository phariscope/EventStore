<?php

namespace Phariscope\EventStore\Tests;

use Phariscope\EventStore\Persistence\StoreEventInMemory;
use Phariscope\EventStore\Tests\Persistence\EventSent;
use PHPUnit\Framework\TestCase;

class PersistEventSubscriberTest extends TestCase
{
    public function testHandle(): void
    {
        $store = new StoreEventInMemory();
        $subscriber = new PersistSubscriberFake($store);

        $event = new EventSent("unEventQuelconque");
        $handled = $subscriber->handle($event);
        $this->assertTrue($handled);
        $storedEvents = $store->allStoredEventsSince($event->occurredOn());

        $this->assertStringContainsString('unEventQuelconque', $storedEvents[0]->getEventBody());
    }

    public function testIsSubscribedTo(): void
    {
        $store = new StoreEventInMemory();
        $subscriber = new PersistSubscriberFake($store);

        $event = new EventSent("unEventQuelconque");
        $this->assertTrue($subscriber->isSubscribedTo($event));
    }
}
