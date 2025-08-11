<?php

namespace Phariscope\EventStore\Tests\Persistence;

use Phariscope\EventStore\Persistence\PersistEventInDatabaseSubscriber;
use PHPUnit\Framework\TestCase;
use Phariscope\EventStore\StoreInterface;

class PersistEventInDatabaseSubscriberTest extends TestCase
{
    public function testCreate(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $persist = new PersistEventInDatabaseSubscriber($pdo, 'test_events');
        $this->assertInstanceOf(StoreInterface::class, $persist->getStore());
    }
}
