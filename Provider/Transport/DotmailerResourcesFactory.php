<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport;

use DotMailer\Api\DataTypes\ApiAccount;
use DotMailer\Api\Resources\IResources;
use DotMailer\Api\Resources\Resources;
use DotMailer\Api\Rest\IClient;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Rest\CacheAwareClient;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Rest\Client;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Rest\DotmailerClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Factory of resources for dotmailer
 */
class DotmailerResourcesFactory
{
    use CacheProviderAwareTrait;

    /**
     * @param string               $username
     * @param string               $password
     * @param LoggerInterface|null $logger
     *
     * @return IResources
     */
    public function createResources($username, $password, LoggerInterface $logger = null)
    {
        $restClient = $this->initClient($username, $password, $logger);

        $resources = new Resources($restClient);

        $account = $resources->GetAccountInfo();
        $this->updateBaseUrl($account, $restClient);

        return $resources;
    }

    /**
     * @param string               $username
     * @param string               $password
     * @param LoggerInterface|null $logger
     *
     * @return AdditionalResource
     */
    public function createAdditionalResource($username, $password, LoggerInterface $logger = null)
    {
        $restClient = $this->initClient($username, $password, $logger);

        $resources = new AdditionalResource($restClient);

        $account = $resources->getAccountInfo();
        $this->updateBaseUrl($account, $restClient);

        return $resources;
    }

    /**
     * @param $username
     * @param $password
     * @param LoggerInterface|null $logger
     * @return IClient|DotmailerClientInterface
     */
    protected function initClient($username, $password, LoggerInterface $logger = null)
    {
        $restClient = new Client($username, $password);
        $cacheClient = new CacheAwareClient($username);
        $cacheClient->setCache($this->getCache());
        $cacheClient->setClient($restClient);

        if ($logger) {
            $restClient->setLogger($logger);
            $cacheClient->setLogger($logger);
        }

        return $cacheClient;
    }

    protected function updateBaseUrl(ApiAccount $account, DotmailerClientInterface $restClient)
    {
        $url = $this->getApiEndpoint($account);
        if ($url) {
            $restClient->setBaseUrl($url);
        }
    }

    /**
     * Fetch API endpoint url from ApiAccount info
     *
     * @param ApiAccount $account
     *
     * @return string|null
     */
    protected function getApiEndpoint(ApiAccount $account)
    {
        $result = $account->properties->toArray();
        $result = array_filter(
            $result,
            function ($item) {
                return $item['name'] === 'ApiEndpoint';
            }
        );

        $apiEndpoint = reset($result);

        if (empty($apiEndpoint)) {
            return null;
        }

        $url = $apiEndpoint['value'];
        // Added '/v2' to the url if its not present
        if (substr($url, -3) !== '/v2') {
            if (!str_ends_with($url, '/')) {
                $url .= '/';
            }
            $url .= 'v2';
        }

        return $url;
    }
}
