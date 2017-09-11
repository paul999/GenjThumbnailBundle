<?php

namespace Genj\ThumbnailBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('genj_thumbnail');

        $rootNode
            ->children()
            ->arrayNode('cloudflare')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('enable')->defaultFalse()->end()
                    ->scalarNode('zone_id')->defaultNull()->end()
                    ->scalarNode('auth_email')->defaultNull()->end()
                    ->scalarNode('auth_key')->defaultNull()->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
