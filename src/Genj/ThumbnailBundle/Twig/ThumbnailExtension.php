<?php

namespace Genj\ThumbnailBundle\Twig;

use Genj\ThumbnailBundle\Imagine\Cache\CacheManager;
use Psr\Log\LoggerInterface;

/**
 * Class ThumbnailExtension
 *
 * @package Genj\ThumbnailBundle\Twig
 */
class ThumbnailExtension extends \Twig_Extension
{
    /** @var CacheManager */
    protected $cacheManager;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param CacheManager    $cacheManager
     * @param LoggerInterface $logger
     */
    public function __construct(CacheManager $cacheManager, LoggerInterface $logger)
    {
        $this->cacheManager = $cacheManager;
        $this->logger       = $logger;
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
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('genj_thumbnail_info', array($this, 'getThumbnailInfo'))
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
     * @param string $src
     *
     * @return array
     */
    public function getThumbnailInfo($src)
    {
        $info      = array('width' => 0, 'height' => 0);
        $imageData = @getimagesize($src);

        if ($imageData === false) {
            $this->logger->warning('GenjThumbnailBundle: getimagesize() on ' . $src . ' failed.');

            return $info;
        }

        $info = array(
            'width'  => $imageData[0],
            'height' => $imageData[1]
        );

        return $info;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'genj_thumbnail';
    }
}