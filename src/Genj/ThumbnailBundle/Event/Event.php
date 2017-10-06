<?php

namespace Genj\ThumbnailBundle\Event;

use Symfony\Component\EventDispatcher\Event as BaseEvent;
use Vich\UploaderBundle\Mapping\PropertyMapping;

/**
 * Class Event
 *
 * @package Genj\ThumbnailBundle\Event
 */
class Event extends BaseEvent
{
    /**
     * @var array $urls Array of URLs to purge from Cloudflare cache
     */
    protected $urls;

    /**
     * @param $urls
     */
    public function __construct($urls)
    {
        $this->urls = $urls;
    }

    /**
     * @return array
     */
    public function getUrls()
    {
        return $this->urls;
    }
}
