<?php

namespace Oro\Bundle\DotmailerBundle\Exception;

class RequiredOptionException extends \Exception implements DotmailerException
{
    /**
     * @param string     $optionName
     * @param int        $code
     * @param \Exception|null $previous
     */
    public function __construct($optionName, $code = 0, \Exception $previous = null)
    {
        $message = sprintf('Option "%s" is required', $optionName);
        parent::__construct($message, $code, $previous);
    }
}
