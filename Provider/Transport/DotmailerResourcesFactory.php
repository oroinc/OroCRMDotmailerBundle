<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport;

use Psr\Log\LoggerInterface;

use DotMailer\Api\Resources\IResources;
use DotMailer\Api\Resources\Resources;

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

        return new Resources($restClient);
    }
}
