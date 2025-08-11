<?php

namespace Phariscope\EventStore\Bridge\Symfony\DependencyInjection;

use Phariscope\EventStore\Persistence\PersistEventInDatabaseSubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;

class EventStoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Expand environment variables if present in config
        $sqlitePathRaw = $config['sqlite_path'] ?? '';
        $sqlitePath = \Phariscope\EventStore\Util\Environment::expand(is_string($sqlitePathRaw) ? $sqlitePathRaw : '');

        $pdoDef = new Definition(\PDO::class);
        $pdoDef->setArguments(['sqlite:' . $sqlitePath]);
        $pdoDef->addMethodCall('setAttribute', [\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION]);

        $container->setDefinition('phariscope_event_store.pdo', $pdoDef);

        $subscriberDef = new Definition(PersistEventInDatabaseSubscriber::class);
        $subscriberDef->setArguments([
            $pdoDef,
            $config['table_name']
        ]);

        $container->setDefinition('phariscope_event_store.subscriber', $subscriberDef);
    }
}
