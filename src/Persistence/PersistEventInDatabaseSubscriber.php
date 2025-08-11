<?php

namespace Phariscope\EventStore\Persistence;

use Phariscope\EventStore\PersistEventSubscriberAbstract;

class PersistEventInDatabaseSubscriber extends PersistEventSubscriberAbstract
{
    public function __construct(\PDO $pdo, string $tableName = 'stored_events')
    {
        parent::__construct(new StoreEventInDatabase($pdo, $tableName));
    }
}
