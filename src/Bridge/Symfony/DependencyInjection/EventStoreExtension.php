<?php

namespace Phariscope\EventStore\Bridge\Symfony\DependencyInjection;

use Phariscope\Event\EventDispatcher;
use Phariscope\EventStore\Bridge\Symfony\EventListener\EventStoreBootListener;
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

        $pdoDef = $this->buildPdoDefinitionWithEnnvironnementVariablesPresentInConfig($config);

        $container->setDefinition('phariscope_event_store.pdo', $pdoDef);

        $tableName = $config['table_name'] ?? '';
        $this->registerSubscriber($container, $pdoDef, is_string($tableName) ? $tableName : '');

        $this->registerBootListenerThatManageEventRecords($container);

        $this->registerDispatcher($container);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildPdoDefinitionWithEnnvironnementVariablesPresentInConfig(array $config): Definition
    {
        $dsn = $config['dsn'] ?? '';
        $dsn = \Phariscope\EventStore\Util\Environment::expand(is_string($dsn) ? $dsn : '');

        $pdoDef = new Definition(\PDO::class);
        $pdoDef->setArguments([$dsn]);
        $pdoDef->addMethodCall('setAttribute', [\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION]);
        return $pdoDef;
    }

    private function registerSubscriber(ContainerBuilder $container, Definition $pdoDef, string $tableName): void
    {
        $subscriberDef = new Definition(PersistEventInDatabaseSubscriber::class);
        $subscriberDef->setArguments([$pdoDef, $tableName]);
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
