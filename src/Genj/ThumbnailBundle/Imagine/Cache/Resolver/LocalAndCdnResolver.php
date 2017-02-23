<?php

namespace Genj\ThumbnailBundle\Imagine\Cache\Resolver;

use Liip\ImagineBundle\Imagine\Cache\Resolver\WebPathResolver as BaseResolver;
use Liip\ImagineBundle\Binary\BinaryInterface;
use Symfony\Component\Filesystem\Filesystem as SfFilesystem;
use League\Flysystem\Filesystem as FlyFilesystem;
use Symfony\Component\Routing\RequestContext;

/**
 * Class LocalAndCdnResolver
 *
 * @package Genj\ThumbnailBundle\Imagine\Cache\Resolver
 */
class LocalAndCdnResolver extends BaseResolver
{
    /**
     * If true interact with localstorage and CDN
     * @var boolean
     */
    protected $useCdn = false;

    /**
     * @var \League\Flysystem\Filesystem
     */
    protected $cdn;

    public function __construct(SfFilesystem $filesystem, RequestContext $requestContext, $webRootDir, $cachePrefix = 'media/cache', $useCdn = false, FlyFilesystem $cdn = null)
    {
        $this->useCdn = $useCdn;
        $this->cdn = $cdn;
        parent::__construct($filesystem, $requestContext, $webRootDir, $cachePrefix);
    }

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

    /**
     * {@inheritDoc}
     * @see \Liip\ImagineBundle\Imagine\Cache\Resolver\WebPathResolver::store()
     */
    public function store(BinaryInterface $binary, $path, $filter)
    {
        // run the file system store action
        parent::store($binary, $path, $filter);

        // put file on cdn
        if ($this->useCdn === true && $this->cdn !== null) {
            if ($this->cdn->has($path) === false) {
                $this->cdn->put($path, $binary->getContent());
            }
        }
    }

    /**
     * {@inheritDoc}
     * @see \Liip\ImagineBundle\Imagine\Cache\Resolver\WebPathResolver::remove()
     */
    public function remove(array $paths, array $filters)
    {
        // run the file system remove action
        parent::remove($paths, $filters);

        // remove files on cdn
        if ($this->useCdn === true && $this->cdn !== null) {
           if (empty($paths)) {
               foreach ($filters as $filter) {
                   $this->cdn->deleteDir($filter);
                }
                return;
            }
            foreach ($paths as $path) {
                foreach ($filters as $filter) {
                    if ($this->cdn->has($path)) {
                        $this->cdn->delete($path);
                    }
                }
            }
        }
    }
}