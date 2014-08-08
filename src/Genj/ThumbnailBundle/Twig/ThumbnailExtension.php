<?php

namespace Genj\ThumbnailBundle\Twig;

use Genj\ThumbnailBundle\Imagine\Cache\CacheManager;

/**
 * Class ThumbnailExtension
 *
 * @package Genj\ThumbnailBundle\Twig
 */
class ThumbnailExtension extends \Twig_Extension
{
    /** @var CacheManager */
    protected $cacheManager;

    /**
     * @param CacheManager $cacheManager
     */
    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('genj_thumbnail', array($this, 'getThumbnailPath'))
        );
    }

    /**
     * @param \stdClass $object
     * @param string    $property
     * @param string    $filter
     * @param bool      $urlForFrontend
     * @param bool      $preview
     *
     * @return mixed
     */
    public function getThumbnailPath($object, $property, $filter, $urlForFrontend = false, $preview = false)
    {
        return new \Twig_Markup(
            $this->cacheManager->getBrowserPathForObject($object, $property, $filter, $urlForFrontend, $preview),
            'utf8'
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'genj_thumbnail';
    }
}