<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Rest;

use DotMailer\Api\Rest\IClient;
use Psr\Log\LoggerAwareInterface;

interface DotmailerClientInterface extends IClient, LoggerAwareInterface
{
    /**
     * @param string $url
     */
    public function setBaseUrl($url);
}
