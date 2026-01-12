<?php

namespace Oro\Bundle\DotmailerBundle\Exception;

/**
 * Indicates a temporary failure in communicating with the Dotmailer REST API.
 */
class RestClientAttemptException extends \Exception implements DotmailerException
{
}
