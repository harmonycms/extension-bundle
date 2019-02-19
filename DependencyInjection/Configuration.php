<?php

namespace Harmony\Bundle\ExtensionBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration
 *
 * @package Harmony\Bundle\ExtensionBundle\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(null, 'array', new MenuTreeBuilder());
        $rootNode    = $treeBuilder->getRootNode();
        $rootNode->menuNodeHierarchy();

        return $treeBuilder;
    }
}