<?php

namespace Genj\ThumbnailBundle\Imagine\Cache;

use Doctrine\Common\Util\ClassUtils;
use Symfony\Component\DependencyInjection\Container;
use Liip\ImagineBundle\Imagine\Cache\CacheManager as BaseCacheManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class CacheManager
 *
 * @package Genj\ThumbnailBundle\Imagine\Cache
 */
class CacheManager extends BaseCacheManager
{
    /** @var Container */
    protected $container;

    /**
     * @param Container $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }

    /**
     * @param \stdClass $object
     * @param string    $property
     * @param string    $filter
     * @param bool      $urlForFrontend
     * @param bool      $preview
     *
     * @return string
     */
    public function getBrowserPathForObject($object, $property, $filter, $urlForFrontend = false, $preview = false)
    {
        $parameters = array(
            'attribute'  => $property,
            'filter'     => $filter,
            'id'         => $object->getId(),
            'slug'       => $object->getSlug(),
            'idShard'    => implode('/', str_split(str_pad($object->getId(), 6, '0', STR_PAD_LEFT), 2)),
            'bundleName' => $this->getBundleNameForObject($object),
            'entityName' => $this->getEntityNameForObject($object),
            '_format'    => $this->getFormat($filter)
        );

        // If we are on upload. or static. subdomain, generate the url with that subdomain
        $currentRequest = $this->container->get('request_stack')->getCurrentRequest();

        if (is_object($currentRequest)) {
            $host = $currentRequest->getHost();
        } else {
            $host = $this->container->getParameter('domain');
        }

        $parts     = explode('.', $host);
        $subdomain = $parts[0];

        if ($subdomain === 'upload' || $subdomain === 'static') {
            $parameters['subdomain'] = $subdomain;
        }

        if ($urlForFrontend && $this->container->has('genj_frontend_url.routing.frontend.generator.url_generator')) {
            $urlGenerator = $this->container->get('genj_frontend_url.routing.frontend.generator.url_generator');
            $url          = $urlGenerator->generateFrontendUrl('genj_thumbnail', $parameters, $preview);
        } else {
            $url = $this->router->generate('genj_thumbnail', $parameters, UrlGeneratorInterface::NETWORK_PATH);
        }

        return $url;
    }

    /**
     * @param \stdClass $object
     *
     * @return string
     */
    protected function getBundleNameForObject($object)
    {
        $reflectionClass = ClassUtils::newReflectionObject($object);

        $namespace  = $reflectionClass->getNamespaceName();
        $bundleName = str_replace('\\Entity', '', $namespace);
        $bundleName = str_replace('\\', '', $bundleName);

        return $bundleName;
    }

    /**
     * @param \stdClass $object
     *
     * @return string
     */
    protected function getEntityNameForObject($object)
    {
        $reflectionClass = new \ReflectionClass(get_class($object));

        $entityName = $reflectionClass->getShortName();

        return $entityName;
    }

    /**
     * @param string $filter
     *
     * @return string
     */
    protected function getFormat($filter)
    {
        $format = '';

        $filterConfig = $this->filterConfig->get($filter);
        if ($filterConfig['filters']['format'][0]) {
            $format = $filterConfig['filters']['format'][0];
        }

        return $format;
    }
}
