<?php

namespace Phariscope\EventStore\Bridge\Symfony\DependencyInjection;

use Phariscope\Event\EventDispatcher;
use Phariscope\Event\Psr14\ListenerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterEventSubscribersPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Trouve tous les services taggés comme event subscribers
        $taggedServices = $container->findTaggedServiceIds('phariscope.event_subscriber');

        foreach ($taggedServices as $id => $tags) {
            // Enregistre chaque subscriber dans l'EventDispatcher
            $container->register('phariscope.event_dispatcher_registration_' . $id)
                ->setClass(\Closure::class)
                ->setFactory([self::class, 'registerSubscriber'])
                ->setArguments([new Reference($id)])
                ->addTag('kernel.event_listener', ['event' => 'kernel.boot', 'method' => '__invoke']);
        }
    }

    public static function registerSubscriber(ListenerInterface $subscriber): \Closure
    {
        return function () use ($subscriber) {
            EventDispatcher::instance()->subscribe($subscriber);
        };
    }
}
