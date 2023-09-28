<?php

namespace Phariscope\EventStore;

use Phariscope\Event\EventAbstract;
use Phariscope\EventStore\Exceptions\EventNotFoundException;

interface StoreInterface
{
    public function append(EventAbstract $event): void;

    /**
     * @return array<int,StoredEvent>
     */
    public function allStoredEventsSince(\DateTimeImmutable|int $past): array;

    /**
     * @throws EventNotFoundException
     */
    public function lastEvent(): StoredEvent;
}
