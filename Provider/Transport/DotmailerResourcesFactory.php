<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport;

use Psr\Log\LoggerInterface;

use DotMailer\Api\Resources\IResources;
use DotMailer\Api\Resources\Resources;
use DotMailer\Api\DataTypes\ApiAccount;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Rest\Client;

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
        $restClient = new Client($username, $password);

        if ($logger) {
            $restClient->setLogger($logger);
        }

        $resources = new Resources($restClient);

        $account = $resources->GetAccountInfo();
        $url     = $this->getApiEndpoint($account);
        if ($url) {
            $restClient->setBaseUrl($url);
        }

        return $resources;
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
