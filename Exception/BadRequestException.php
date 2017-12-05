<?php

namespace Oro\Bundle\DotmailerBundle\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class BadRequestException extends HttpException implements DotmailerException
{
    /**
     * @param string|null $message
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(400, $message, $previous, [], $code);
    }
}
