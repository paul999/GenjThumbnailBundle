<?php

namespace Genj\ThumbnailBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;

class ThumbnailCleaner implements EventSubscriber {

    protected $annotationDriver, $filterConfig, $cacheManager;

    public function __construct($annotationDriver, $filterConfig, $cacheManager)
    {
        $this->annotationDriver = $annotationDriver;
        $this->filterConfig     = $filterConfig;
        $this->cacheManager     = $cacheManager;
    }

    public function getSubscribedEvents()
    {
        return array(
            'postUpdate',
            'postDelete',
        );
    }

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
     * @param \stdClass $object
     *
     * @todo Would be even better if this only happens for the uploadableField which was updated
     */
    public function deleteCachedThumbnails($entity, $uploadableField)
    {
        $filters = array_keys($this->filterConfig->all());

        foreach ($filters as $filter) {
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