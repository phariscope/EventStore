<?php

namespace Phariscope\EventStore\Bridge\Symfony\DependencyInjection;

use Phariscope\Event\EventDispatcher;
use Phariscope\EventStore\Bridge\Symfony\EventListener\EventStoreBootListener;
use Phariscope\EventStore\Bridge\Symfony\Factory\PdoFactory;
use Phariscope\EventStore\Persistence\PersistEventInDatabaseSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class EventStoreExtension extends Extension
{
    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        /** @var array<string, mixed> $config */
        $config = $this->processConfiguration($configuration, $configs);

        $this->registerPdoFactory($container, $config);

        $tableName = $config['table_name'] ?? '';
        $this->registerSubscriber($container, is_string($tableName) ? $tableName : '');

        $this->registerBootListenerThatManageEventRecords($container);

        $this->registerDispatcher($container);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerPdoFactory(ContainerBuilder $container, array $config): void
    {
        $dsn = $config['dsn'] ?? '';
        $dsnTemplate = is_string($dsn) ? $dsn : '';

        // Enregistrer la factory
        $factoryDef = new Definition(PdoFactory::class);
        $factoryDef->setArguments([$dsnTemplate]);
        $container->setDefinition('phariscope_event_store.pdo_factory', $factoryDef);

        // Enregistrer le service PDO qui utilise la factory
        $pdoDef = new Definition(\PDO::class);
        $pdoDef->setFactory([new Reference('phariscope_event_store.pdo_factory'), 'createPdo']);
        $container->setDefinition('phariscope_event_store.pdo', $pdoDef);
    }

    private function registerSubscriber(ContainerBuilder $container, string $tableName): void
    {
        $subscriberDef = new Definition(PersistEventInDatabaseSubscriber::class);
        $subscriberDef->setArguments([new Reference('phariscope_event_store.pdo'), $tableName]);
        $container->setDefinition('phariscope_event_store.subscriber', $subscriberDef);
    }

    private function registerBootListenerThatManageEventRecords(ContainerBuilder $container): void
    {
        $listenerDef = new Definition(EventStoreBootListener::class);
        $listenerDef->setArguments([new Reference('phariscope_event_store.subscriber')]);
        $listenerDef->addTag('kernel.event_subscriber');
        $container->setDefinition('phariscope_event_store.boot_listener', $listenerDef);
    }

    private function registerDispatcher(ContainerBuilder $container): void
    {
        $dispatcherDef = new Definition(EventDispatcher::class);
        $dispatcherDef->setArguments([new Reference('phariscope_event_store.subscriber')]);
        $container->setDefinition('phariscope_event_store.dispatcher', $dispatcherDef);
    }
}
