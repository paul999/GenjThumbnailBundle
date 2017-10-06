<?php

namespace Genj\ThumbnailBundle\EventSubscriber;

use Imagine\Exception\RuntimeException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Vich\UploaderBundle\Event\Event as VichEvent;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Genj\ThumbnailBundle\Event\Event as GenjEvent;

/**
 * Class CloudflareVichUploadPurger
 *
 * @package Genj\ThumbnailBundle\EventListener
 */
class CloudflarePurger implements EventSubscriberInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $cdnDomain;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var UploaderHelper
     */
    protected $vichUploaderHelper;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'vich_uploader.pre_remove'        => array('processVichEvent'),
            'genj_thumbnail.cloudflare_purge' => array('processGenjEvent')
        );
    }

    /**
     * @param array           $config
     * @param string          $cdnDomain
     * @param UploaderHelper  $vichUploaderHelper
     * @param LoggerInterface $logger
     */
    public function __construct($config, $cdnDomain, UploaderHelper $vichUploaderHelper, LoggerInterface $logger)
    {
        $this->config             = $config;
        $this->cdnDomain          = $cdnDomain;
        $this->vichUploaderHelper = $vichUploaderHelper;
        $this->logger             = $logger;
    }

    /**
     * @param VichEvent $event
     *
     * @return void
     * @throws \Exception
     */
    public function processVichEvent(VichEvent $event)
    {
        $object  = $event->getObject();
        $mapping = $event->getMapping();

        // VichUploader knows nothing about our CDN domain, so we have to build the URLs manually
        $path = $this->vichUploaderHelper->asset($object, $mapping->getFilePropertyName(), get_class($object));
        $urls = array(
            'http://' . $this->cdnDomain . $path,
            'https://' . $this->cdnDomain . $path,
        );

        $this->purge($urls);
    }

    /**
     * @param GenjEvent $event
     *
     * @return void
     * @throws \Exception
     */
    public function processGenjEvent(GenjEvent $event)
    {
        $this->purge($event->getUrls());
    }

    /**
     * @param array $urls
     *
     * @return void
     * @throws \Exception
     */
    protected function purge($urls)
    {
        // Only continue if user has enabled Cloudflare
        if ($this->config['enable'] === false) {
            return;
        }

        // Only continue if there are URLs to purge
        if (count($urls) < 1) {
            return;
        }

        // Make sure that we have all settings
        if (!$this->config['zone_id'] || !$this->config['auth_email'] || !$this->config['auth_key']) {
            $this->logger->error(
                'GenjThumbnailBundle has Cloudflare enabled, but we are missing Cloudflare settings! Not purging...'
            );

            return;
        }

        // Cloudflare is enabled and we have all configuration settings, so proceed with purge
        try {
            $this->sendPurgeRequest($urls);
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * Prepares and sends the DELETE request to purge a file from Cloudflare cache.
     *
     * @param array $urls
     *
     * @return void
     * @throws RuntimeException
     */
    protected function sendPurgeRequest($urls)
    {
        // Set variables for purge request
        $endpoint = sprintf('https://api.cloudflare.com/client/v4/zones/%s/purge_cache', $this->config['zone_id']);
        $payload  = array('files' => $urls);
        $headers  = array(
            'Content-Type: application/json',
            'X-Auth-Email: ' . $this->config['auth_email'],
            'X-Auth-Key: ' . $this->config['auth_key']
        );

        // Perform purge request
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response = curl_exec($ch);
        curl_close ($ch);

        $this->handleResponse($response);
    }

    /**
     * Handle Cloudflare response.
     *
     * @param string $response
     *
     * @return void
     * @throws RuntimeException
     */
    protected function handleResponse($response) {
        if ($response === null) {
            throw new RuntimeException('GenjThumbnailBundle got empty response Cloudflare. Response: ' . $response);
        }

        $jsonResponse = json_decode($response);
        if ($jsonResponse === null) {
            throw new RuntimeException('GenjThumbnailBundle failed to decode Cloudflare response: ' . $response);
        }

        if ($jsonResponse->success !== true) {
            throw new RuntimeException('GenjThumbnailBundle failed to purge Cloudflare cache: ' . $response, true);
        }
    }
}
