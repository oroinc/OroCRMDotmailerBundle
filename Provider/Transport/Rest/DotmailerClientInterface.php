<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Rest;

use DotMailer\Api\Rest\IClient;
use Psr\Log\LoggerAwareInterface;

/**
 * Client interface wrapper for dotmailer client and logger awere interfaces
 */
interface DotmailerClientInterface extends IClient, LoggerAwareInterface
{
    public function setBaseUrl(string $url): void;
}
