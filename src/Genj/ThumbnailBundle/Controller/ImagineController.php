<?php

namespace Genj\ThumbnailBundle\Controller;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Imagine\Exception\RuntimeException;
use Liip\ImagineBundle\Model\Binary;
use Liip\ImagineBundle\Exception\Binary\Loader\NotLoadableException;
use Liip\ImagineBundle\Controller\ImagineController as BaseImagineController;

/**
 * Class ImagineController
 *
 * @package Genj\ThumbnailBundle\Imagine\Controller
 */
class ImagineController extends BaseImagineController
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
     * This action applies a given filter to a given image, optionally saves the image and outputs it to the browser at the same time.
     *
     * @param string  $bundleName
     * @param string  $entityName
     * @param string  $attribute
     * @param string  $filter
     * @param string  $slug
     * @param int     $id
     * @param string  $_format
     * @param Request $request
     *
     * @throws \RuntimeException
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     *
     * @return RedirectResponse
     */
    public function filterActionForObject($bundleName, $entityName, $attribute, $filter, $slug, $id, $_format, Request $request)
    {
        $path = $request->getPathInfo();
        if (substr($path, 0, 1) == '/') {
            $path = substr($path, 1);
        }

        try {
            // Retrieve the corresponding Entity Manager and Repository
            $entityManager    = $this->container->get('doctrine')->getManagerForClass($bundleName .':'. $entityName);
            $entityRepository = $entityManager->getRepository($bundleName .':'. $entityName);
            $entity           = $entityRepository->findOneById($id);

            // Retrieve the file path using the vich uploader
            $helper    = $this->container->get('vich_uploader.storage');
            $filePath  = $helper->resolvePath($entity, $attribute);
            $pathParts = explode('../web/', $filePath);
            $filePath  = $pathParts[count($pathParts) - 1];

            // Try loading the image
            try {
                $binary = $this->container->get('liip_imagine.data.manager')->find($filter, $filePath);
            } catch (NotLoadableException $e) {
                throw new NotFoundHttpException('Source image could not be found', $e);
            }

            // Apply all filters
            $filteredBinary = $this->container->get('liip_imagine.filter.manager')->applyFilter($binary, $filter);

            // If configured result format is not the same as the source file, convert to configured format
            $filteredBinary = $this->convertToConfiguredFormat($filteredBinary, $filter);

            // Store resulting binary
            $this->store($filteredBinary, $path, $filter);

            $response = new Response($filteredBinary->getContent(), 200);
            $response->headers->set('Content-Type', $filteredBinary->getMimeType());

            return $response;
        } catch (RuntimeException $e) {
            throw new \RuntimeException(sprintf('Unable to create image for path "%s" and filter "%s". Message was "%s"', $path, $filter, $e->getMessage()), 0, $e);
        }
    }

    /**
     * @param Binary $filteredBinary
     * @param string $filter
     *
     * @return Binary
     */
    protected function convertToConfiguredFormat(Binary $filteredBinary, $filter)
    {
        $mimeTypeGuesser  = $this->container->get('liip_imagine.binary.mime_type_guesser');
        $extensionGuesser = $this->container->get('liip_imagine.extension_guesser');
        $filterConfig     = $this->container->get('liip_imagine.filter.configuration')->get($filter);

        $currentFormat = $extensionGuesser->guess($filteredBinary->getMimeType());
        $targetFormat  = $filterConfig['filters']['format'][0];

        if ($currentFormat !== $targetFormat) {
            $imagine = $this->container->get('liip_imagine');
            $image   = $imagine->load($filteredBinary->getContent());
            $binary  = $image->get($targetFormat);

            $mimeType       = $mimeTypeGuesser->guess($binary);
            $extension      = $extensionGuesser->guess($mimeType);
            $filteredBinary = new Binary(
                $binary,
                $mimeType,
                $extension
            );
        }

        return $filteredBinary;
    }

    /**
     * We only want to store thumbnail cache for environments which have the Twig cache enabled as well
     *
     * @param string $filteredBinary
     * @param string $path
     * @param string $filter
     */
    protected function store($filteredBinary, $path, $filter)
    {
        if ($this->container->get('twig')->getCache()) {
            $this->cacheManager->store(
                $filteredBinary,
                $path,
                $filter
            );
        }
    }
}