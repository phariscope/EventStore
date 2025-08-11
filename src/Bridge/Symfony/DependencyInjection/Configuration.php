<?php

namespace Phariscope\EventStore\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\ScalarNodeDefinition;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('event_store');
        $rootNode = $treeBuilder->getRootNode();

        if ($rootNode instanceof ArrayNodeDefinition) {
            /** @var NodeBuilder $children */
            $children = $rootNode->children();

            /** @var ScalarNodeDefinition $sqlite */
            $sqlite = $children->scalarNode('sqlite_path');
            $sqlite->isRequired()->cannotBeEmpty();

            /** @var ScalarNodeDefinition $table */
            $table = $children->scalarNode('table_name');
            $table->defaultValue('stored_events')->cannotBeEmpty();
        }

        return $treeBuilder;
    }
}
