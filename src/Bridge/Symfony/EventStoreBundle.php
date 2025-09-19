<?php

namespace Phariscope\EventStore\Bridge\Symfony;

use Phariscope\EventStore\Bridge\Symfony\DependencyInjection\RegisterEventSubscribersPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Phariscope\EventStore\Bridge\Symfony\DependencyInjection\EventStoreExtension;

class EventStoreBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new RegisterEventSubscribersPass());
    }

    public function getContainerExtension(): ?\Symfony\Component\DependencyInjection\Extension\ExtensionInterface
    {
        return new EventStoreExtension();
    }
}
