<?php

namespace Genj\ThumbnailBundle\Imagine\Filter\Loader;

use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;

/**
 * Class FormatFilterLoader
 *
 * @package Genj\ThumbnailBundle\Imagine\Filter\Loader
 */
class FormatFilterLoader implements LoaderInterface
{
    /**
     * This filter does nothing. The configuration for the filter is used in the ImagineController. It's only here
     * because this seemed the easiest place to add this configuration option.
     *
     * @param ImageInterface $image
     * @param array          $options
     *
     * @return ImageInterface
     */
    function load(ImageInterface $image, array $options = array())
    {
        return $image;
    }
}