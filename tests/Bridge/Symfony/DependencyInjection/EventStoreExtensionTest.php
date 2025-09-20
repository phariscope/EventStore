<?php

namespace Phariscope\EventStore\Tests\Bridge\Symfony\DependencyInjection;

use Phariscope\Event\EventDispatcher;
use Phariscope\EventStore\Bridge\Symfony\DependencyInjection\EventStoreExtension;
use Phariscope\EventStore\Bridge\Symfony\EventListener\EventStoreBootListener;
use Phariscope\EventStore\Persistence\PersistEventInDatabaseSubscriber;
use Phariscope\EventStore\Persistence\StoreEventInDatabase;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EventStoreExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        // Arrange
        $sut = new EventStoreExtension();
        $config = [
            'dsn' => 'sqlite::memory:',
            'table_name' => 'test_events',
        ];

        $container = new ContainerBuilder();

        // Act
        $sut->load([$config], $container);

        // Assert
        $this->assertPdoIsDefined($container);
        $this->assertBootListenerIsDefined($container);
        $this->assertSubscriberIsDefined($container);
    }

    private function assertPdoIsDefined(ContainerBuilder $container): void
    {
        $pdo = $container->get('phariscope_event_store.pdo');
        $this->assertInstanceOf(\PDO::class, $pdo);
        $this->assertEquals('sqlite', $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
    }

    private function assertBootListenerIsDefined(ContainerBuilder $container): void
    {
        $listener = $container->get('phariscope_event_store.boot_listener');
        $this->assertInstanceOf(EventStoreBootListener::class, $listener);
    }

    private function assertSubscriberIsDefined(ContainerBuilder $container): void
    {
        $subscriber = $container->get('phariscope_event_store.subscriber');
        $this->assertInstanceOf(PersistEventInDatabaseSubscriber::class, $subscriber);
        $store = $subscriber->getStore();
        $this->assertInstanceOf(StoreEventInDatabase::class, $store);
        $this->assertEquals('test_events', $store->getTableName());
    }
}
