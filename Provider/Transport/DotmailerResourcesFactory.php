<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport;

use Psr\Log\LoggerInterface;

use DotMailer\Api\Resources\IResources;
use DotMailer\Api\Resources\Resources;
use DotMailer\Api\DataTypes\ApiAccount;

use Oro\Bundle\DotmailerBundle\Provider\Transport\Rest\Client;
use Oro\Bundle\DotmailerBundle\Provider\Transport\AdditionalResource;

class DotmailerResourcesFactory
{
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
        $this->updateEndpoint($account, $restClient);

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
        $this->updateEndpoint($account, $restClient);

        return $resources;
    }

    /**
     * @param $username
     * @param $password
     * @param LoggerInterface|null $logger
     * @return Client
     */
    protected function initClient($username, $password, LoggerInterface $logger = null)
    {
        $restClient = new Client($username, $password);

        if ($logger) {
            $restClient->setLogger($logger);
        }

        return $restClient;
    }

    /**
     * @param ApiAccount $account
     * @param Client $restClient
     */
    protected function updateEndpoint(ApiAccount $account, Client $restClient)
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
            if (substr($url, -1) !== '/') {
                $url .= '/';
            }
            $url .= 'v2';
        }

        return $url;
    }
}
