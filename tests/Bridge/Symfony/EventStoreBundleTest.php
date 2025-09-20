<?php

namespace Phariscope\EventStore\Tests\Bridge\Symfony;

use Phariscope\Event\EventDispatcher;
use Phariscope\EventStore\Bridge\Symfony\EventListener\EventStoreBootListener;
use Phariscope\EventStore\Bridge\Symfony\EventStoreBundle;
use Phariscope\EventStore\Persistence\PersistEventInDatabaseSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class EventStoreBundleTest extends TestCase
{
    public function testEventDispatcherIsRegistered(): void
    {
        // Arrange
        $sut = new EventStoreBundle();
        $container = new ContainerBuilder();

        // Act
        // 1. Charger l'extension pour enregistrer les services
        $extension = $sut->getContainerExtension();
        $this->assertNotNull($extension);
        $extension->load([['dsn' => 'sqlite::memory:', 'table_name' => 'events']], $container);

        // 2. Construire le bundle (ajoute le compiler pass)
        $sut->build($container);

        // 3. Rendre le service public pour le test
        $container->getDefinition('phariscope_event_store.boot_listener')->setPublic(true);

        // 4. Compiler le container pour déclencher les compiler passes
        $container->compile();

        // 5. Simuler le boot en récupérant et exécutant le boot listener
        /** @var EventStoreBootListener $bootListener */
        $bootListener = $container->get('phariscope_event_store.boot_listener');
        $requestEvent = $this->createMock(RequestEvent::class);
        $requestEvent->method('isMainRequest')->willReturn(true);
        $bootListener->onKernelRequest($requestEvent);

        // Assert
        $this->assertTrue(EventDispatcher::instance()->hasSubscriber(PersistEventInDatabaseSubscriber::class));
    }
}
