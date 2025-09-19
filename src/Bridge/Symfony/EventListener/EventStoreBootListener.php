<?php

namespace Phariscope\EventStore\Bridge\Symfony\EventListener;

use Phariscope\Event\EventDispatcher;
use Phariscope\EventStore\Persistence\PersistEventInDatabaseSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EventStoreBootListener implements EventSubscriberInterface
{
    private PersistEventInDatabaseSubscriber $subscriber;
    private bool $initialized = false;

    public function __construct(PersistEventInDatabaseSubscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->initialized && $event->isMainRequest()) {
            EventDispatcher::instance()->subscribe($this->subscriber);
            $this->initialized = true;
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1024], // Priorité haute
        ];
    }
}
