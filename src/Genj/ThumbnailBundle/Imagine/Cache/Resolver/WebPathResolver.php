<?php

namespace Genj\ThumbnailBundle\Imagine\Cache\Resolver;

use Liip\ImagineBundle\Imagine\Cache\Resolver\WebPathResolver as BaseResolver;
use Liip\ImagineBundle\Binary\BinaryInterface;

/**
 * Class WebPathResolver
 *
 * @package Genj\ThumbnailBundle\Imagine\Cache\Resolver
 */
class WebPathResolver extends BaseResolver
{
    /**
     * Override to return just the path itself. We determine our path using the routing, so there is no need to change
     * the path here.
     *
     * @param string $path
     * @param string $filter
     *
     * @return string
     */
    protected function getFileUrl($path, $filter)
    {
        return $path;
    }
}