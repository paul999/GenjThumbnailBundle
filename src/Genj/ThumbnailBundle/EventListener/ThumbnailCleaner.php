<?php

namespace Genj\ThumbnailBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Vich\UploaderBundle\Driver\AnnotationDriver;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Genj\ThumbnailBundle\Imagine\Cache\CacheManager;

/**
 * Class ThumbnailCleaner
 *
 * @package Genj\ThumbnailBundle\EventListener
 */
class ThumbnailCleaner implements EventSubscriber {
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
     * @param AnnotationDriver    $annotationDriver
     * @param FilterConfiguration $filterConfig
     * @param CacheManager        $cacheManager
     */
    public function __construct(AnnotationDriver $annotationDriver, FilterConfiguration $filterConfig, CacheManager $cacheManager)
    {
        $this->annotationDriver = $annotationDriver;
        $this->filterConfig     = $filterConfig;
        $this->cacheManager     = $cacheManager;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            'postUpdate',
            'postDelete',
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

        $entityClass      = new \ReflectionClass($entity);
        $uploadableFields = $this->annotationDriver->readUploadableFields($entityClass);

        foreach ($uploadableFields as $uploadableField) {
            if (isset($entityChangeSet[$uploadableField->getFileNameProperty()])) {
                $this->deleteCachedThumbnails($entity, $uploadableField);
            }
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postDelete(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        $entityClass      = new \ReflectionClass($entity);
        $uploadableFields = $this->annotationDriver->readUploadableFields($entityClass);

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
        $filters = array_keys($this->filterConfig->all());

        foreach ($filters as $filter) {
            if ($filter !== 'cache') {
                $thumbnailUrl = $this->cacheManager->getBrowserPathForObject(
                    $entity,
                    $uploadableField->getPropertyName(),
                    $filter,
                    true
                );

                $thumbnailPath = parse_url($thumbnailUrl, PHP_URL_PATH);
                $thumbnailPath = preg_replace('/^(\/dev\.php)/', '', $thumbnailPath, 1);

                $this->cacheManager->remove($thumbnailPath, $filter);
            }
        }
    }
}