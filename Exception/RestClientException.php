<?php

namespace Oro\Bundle\DotmailerBundle\Exception;

use RestClient\Exception;

/**
 * Base exception for errors encountered when communicating with the Dotmailer REST API.
 */
class RestClientException extends Exception implements DotmailerException
{
}
