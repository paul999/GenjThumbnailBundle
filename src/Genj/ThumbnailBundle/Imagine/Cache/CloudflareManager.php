<?php

namespace Genj\ThumbnailBundle\Imagine\Cache;

use Psr\Log\LoggerInterface;

/**
 * Class CloudflareManager
 *
 * @package Genj\ThumbnailBundle\Imagine\Cache
 */
class CloudflareManager
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param array           $config
     * @param LoggerInterface $logger
     */
    public function __construct($config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param string $thumbnailUrl
     *
     * @return void
     */
    public function remove($thumbnailUrl)
    {
        // Only continue if user has enabled Cloudflare
        if ($this->config['enable'] === false) {
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
            $this->purge($thumbnailUrl);
        } catch (\Exception $ex) {
            $this->logger->error($ex->getMessage());
            throw $ex;
        }
    }

    /**
     * Prepares and sends the DELETE request to purge a file from Cloudflare cache.
     *
     * @param string $thumbnailUrl
     *
     * @return void
     */
    protected function purge($thumbnailUrl)
    {
        // Set variables for purge request
        $url     = sprintf('https://api.cloudflare.com/client/v4/zones/%s/purge_cache', $this->config['zone_id']);
        $payload = array('files' => array($thumbnailUrl));
        $headers = array(
            'Content-Type: application/json',
            'X-Auth-Email: ' . $this->config['auth_email'],
            'X-Auth-Key: ' . $this->config['auth_key']
        );

        // Perform purge request
        $ch  = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
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
     */
    protected function handleResponse($response) {
        if ($response === null) {
            throw new \RuntimeException('GenjThumbnailBundle got empty response Cloudflare. Response: ' . $response);
        }

        $jsonResponse = json_decode($response);
        if ($jsonResponse === null) {
            throw new \RuntimeException('GenjThumbnailBundle failed to decode Cloudflare response: ' . $response);
        }

        if ($jsonResponse->success !== true) {
            throw new \RuntimeException('GenjThumbnailBundle failed to purge Cloudflare cache: ' . $response, true);
        }
    }
}
