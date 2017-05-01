<?php

namespace Genj\ThumbnailBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Genj\ThumbnailBundle\DependencyInjection\Compiler\CompilerPass;
use Genj\ThumbnailBundle\DependencyInjection\LocalAndCdnResolverFactory;

/**
 * Class GenjThumbnailBundle
 *
 * @package Genj\ThumbnailBundle
 */
class GenjThumbnailBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('liip_imagine');
        $extension->addResolverFactory(new LocalAndCdnResolverFactory());
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'LiipImagineBundle';
    }
}