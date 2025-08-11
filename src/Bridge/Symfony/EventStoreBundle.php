<?php

namespace Phariscope\EventStore\Bridge\Symfony;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Phariscope\EventStore\Bridge\Symfony\DependencyInjection\EventStoreExtension;

class EventStoreBundle extends Bundle
{
    public function getContainerExtension(): ?\Symfony\Component\DependencyInjection\Extension\ExtensionInterface
    {
        return new EventStoreExtension();
    }
}
