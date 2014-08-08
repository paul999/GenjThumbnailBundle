<?php

namespace Genj\ThumbnailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class CompilerPass
 *
 * @package Genj\ThumbnailBundle\DependencyInjection\Compiler
 */
class CompilerPass implements CompilerPassInterface
{
    /**
     * Make the container available in the ImagineController
     *
     * @param ContainerBuilder $containerBuilder
     */
    public function process(ContainerBuilder $containerBuilder)
    {
        if (!$containerBuilder->hasDefinition('liip_imagine.controller')) {
            return;
        }

        $containerBuilder->getDefinition('liip_imagine.controller')
            ->addMethodCall('setContainer', array(
                    new Reference('service_container'),
                )
            );

        $containerBuilder->getDefinition('liip_imagine.cache.manager')
            ->addMethodCall('setContainer', array(
                    new Reference('service_container'),
                )
            );
    }
}