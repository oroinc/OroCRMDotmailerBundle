<?php

namespace OroCRM\Bundle\DotmailerBundle\Provider\Transport\Rest;

use RestClient\Request;

use DotMailer\Api\Rest\IClient;
use DotMailer\Api\Rest;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

use OroCRM\Bundle\DotmailerBundle\Exception\RestClientAttemptException;
use OroCRM\Bundle\DotmailerBundle\Exception\RestClientException;

/**
 * Overload Rest Client class from romanpitak/dotmailer-api-v2-php-client bundle
 */
class Client implements IClient, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var int */
    protected $attempted;

    /** @var bool */
    protected $multipleAttemptsEnabled = true;

    /** @var array */
    protected $sleepBetweenAttempt = [5, 10, 20, 40];

    /** @var \RestClient\Client */
    protected $restClient;

    /**
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        $this->restClient = new \RestClient\Client(
            [
                Request::BASE_URL_KEY => 'https://api.dotmailer.com/v2',
                Request::USERNAME_KEY => $username,
                Request::PASSWORD_KEY => $password,
                Request::USER_AGENT_KEY => 'romanpitak/dotmailer-api-v2-php-client',
                Request::HEADERS_KEY => [
                    'Content-Type' => 'application/json',
                ],
                Request::CURL_OPTIONS_KEY => [
                    CURLOPT_SSL_VERIFYPEER => false,
                ],
            ]
        );

    }

    /**
     * @param array|string $param_arr
     * @param array        $responses
     * @throws RestClientAttemptException
     * @throws RestClientException
     *
     * @return string|null
     */
    public function execute($param_arr, $responses = [])
    {
        // when only url is supplied
        if (is_string($param_arr)) {
            $param_arr = [$param_arr];
        }

        $callback = [$this->restClient, 'newRequest'];
        /** @var Request $request */
        $request = call_user_func_array($callback, $param_arr);

        try {
            $response = $request->getResponse();
            $returnCode = $response->getInfo()->http_code;

            // is there a special action to be done?
            if (isset($responses[$returnCode])) {
                return call_user_func($responses[$returnCode], $response);
            }

            switch ((int)$returnCode) {
                case 200:
                case 201:
                case 202:
                    return $response->getParsedResponse();
                    break;
                case 204: // no content
                    return null;
                default:
                    throw new RestClientAttemptException(
                        sprintf('Response HTTP CODE: %s, Body: %s', $returnCode, $response->getParsedResponse())
                    );
            }

        } catch (\Exception $e) {
            if ($this->isAttemptNecessary()) {
                $result = $this->makeNewAttempt($param_arr, $responses);
            } else {
                $this->resetAttemptCount();

                throw new RestClientException($e->getMessage());
            }
        }

        $this->resetAttemptCount();

        return $result;
    }

    /**
     * @return bool
     */
    protected function isAttemptNecessary()
    {
        return $this->multipleAttemptsEnabled && ($this->attempted < count($this->sleepBetweenAttempt) - 1);
    }

    /**
     * Set count attempt to 0
     */
    protected function resetAttemptCount()
    {
        $this->attempted = 0;
    }

    /**
     * Make new attempt
     *
     * @param array|string $param_arr
     * @param array        $responses
     *
     * @return string|null
     */
    protected function makeNewAttempt($param_arr, $responses = [])
    {
        $this->logAttempt();
        sleep($this->getSleepBetweenAttempt());
        ++$this->attempted;

        return $this->execute($param_arr, $responses);
    }

    /**
     * Log attempt
     */
    protected function logAttempt()
    {
        if (!empty($this->logger)) {
            $this->logger->warning(
                '[Warning] Attempt number ' . ($this->attempted + 1)
                . ' with ' . $this->getSleepBetweenAttempt() . ' sec delay.'
            );
        }
    }

    /**
     * Returns the current item by $attempted or the last of them
     *
     * @return int
     */
    protected function getSleepBetweenAttempt()
    {
        if (!empty($this->sleepBetweenAttempt[$this->attempted])) {
            return (int)$this->sleepBetweenAttempt[$this->attempted];
        }

        return (int)end($this->sleepBetweenAttempt);
    }
}
