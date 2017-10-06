<?php

namespace Genj\ThumbnailBundle\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Vich\UploaderBundle\Metadata\Driver\AnnotationDriver;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Genj\ThumbnailBundle\Event\Event;
use Genj\ThumbnailBundle\Imagine\Cache\CacheManager;

/**
 * Class ThumbnailCleaner
 *
 * @package Genj\ThumbnailBundle\EventListener
 */
class ThumbnailCleaner implements EventSubscriber
{
    /**
     * @var AnnotationDriver
     */
    protected $annotationDriver;

    /**
     * @var FilterConfiguration
     */
    protected $filterConfig;

    /**
     * @var CacheManager
     */
    protected $cacheManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param AnnotationDriver         $annotationDriver
     * @param FilterConfiguration      $filterConfig
     * @param CacheManager             $cacheManager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(AnnotationDriver $annotationDriver, FilterConfiguration $filterConfig, CacheManager $cacheManager, EventDispatcherInterface $dispatcher)
    {
        $this->annotationDriver  = $annotationDriver;
        $this->filterConfig      = $filterConfig;
        $this->cacheManager      = $cacheManager;
        $this->dispatcher        = $dispatcher;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'postUpdate',
            'preRemove',
        );
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $em     = $args->getEntityManager();
        $uow    = $em->getUnitOfWork();
        $entity = $args->getEntity();

        $entityChangeSet = $uow->getEntityChangeSet($entity);

        $entityClass = new \ReflectionClass($entity);
        $metaData    = $this->annotationDriver->loadMetadataForClass($entityClass);
        if (!is_object($metaData) || !property_exists($metaData, 'fields')) {
            return;
        }

        $uploadableFields = $metaData->fields;

        foreach ($uploadableFields as $uploadableField) {
            if (isset($entityChangeSet[$uploadableField['fileNameProperty']])) {
                $this->deleteCachedThumbnails($entity, $uploadableField);
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $entity      = $args->getEntity();
        $entityClass = new \ReflectionClass($entity);
        $metaData    = $this->annotationDriver->loadMetadataForClass($entityClass);

        if (!is_object($metaData) || !property_exists($metaData, 'fields')) {
            return;
        }

        $uploadableFields = $metaData->fields;

        foreach ($uploadableFields as $uploadableField) {
            $this->deleteCachedThumbnails($entity, $uploadableField);
        }
    }

    /**
     * Delete all cached thumbnails when an entity gets updated
     *
     * @param \stdClass $entity
     * @param string    $uploadableField
     *
     * @todo Would be even better if this only happens for the uploadableField which was updated
     */
    public function deleteCachedThumbnails($entity, $uploadableField)
    {
        $filters       = array_keys($this->filterConfig->all());
        $thumbnailUrls = array();

        foreach ($filters as $filter) {
            if ($filter !== 'cache') {
                $thumbnailUrl = $this->cacheManager->getBrowserPathForObject(
                    $entity,
                    $uploadableField['propertyName'],
                    $filter,
                    true
                );

                // Remember this URL for the event dispatch to CloudflarePurger
                $thumbnailUrls[] = $thumbnailUrl;

                $thumbnailPath = parse_url($thumbnailUrl, PHP_URL_PATH);
                $thumbnailPath = preg_replace('/^(\/dev\.php)/', '', $thumbnailPath, 1);
                $thumbnailPath = ltrim($thumbnailPath, '/');

                $this->cacheManager->remove($thumbnailPath, $filter);
            }
        }

        $this->dispatcher->dispatch('genj_thumbnail.cloudflare_purge', new Event($thumbnailUrls));
    }
}
