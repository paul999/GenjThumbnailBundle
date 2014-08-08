<?php

namespace Genj\ThumbnailBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Genj\ThumbnailBundle\DependencyInjection\Compiler\CompilerPass;

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

        $container->addCompilerPass(new CompilerPass());
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'LiipImagineBundle';
    }
}