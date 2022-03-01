<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Rest;

use Oro\Bundle\DotmailerBundle\Exception\RestClientAttemptException;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Psr\Log\LoggerAwareTrait;
use RestClient\Request;

/**
 * Override Rest Client class from romanpitak/dotmailer-api-v2-php-client bundle is not possible because of
 * private fields.
 */
class Client implements DotmailerClientInterface
{
    use LoggerAwareTrait;

    const CONNECT_TIMEOUT = 300;
    const EXECUTE_TIMEOUT = 360;

    /** @var int */
    protected $attempted = 0;

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
                Request::BASE_URL_KEY => 'https://api.dotmailer.com/v2/',
                Request::USERNAME_KEY => $username,
                Request::PASSWORD_KEY => $password,
                Request::USER_AGENT_KEY => 'romanpitak/dotmailer-api-v2-php-client',
                Request::HEADERS_KEY => [
                    'Content-Type' => 'application/json',
                ],
                Request::CURL_OPTIONS_KEY => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                    CURLOPT_TIMEOUT => self::EXECUTE_TIMEOUT,
                ],
            ]
        );
    }

    public function setBaseUrl(string $url): void
    {
        $this->restClient->setOption(Request::BASE_URL_KEY, $url);
    }

    /**
     * @param array|string $paramArr
     * @param array        $responses
     * @throws RestClientAttemptException
     * @throws RestClientException
     *
     * @return string|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute($paramArr, $responses = [])
    {
        // when only url is supplied
        if (is_string($paramArr)) {
            $paramArr = [$paramArr];
        }

        list($requestUrl, $requestMethod, $requestData) = array_pad(array_values($paramArr), 3, null);
        $responseCode = null;
        $responseBody = null;

        try {
            $callback = [$this->restClient, 'newRequest'];
            /** @var Request $request */
            $request = call_user_func_array($callback, $paramArr);

            $response = $request->getResponse();
            $responseCode = $response->getInfo()->http_code;
            $responseBody = $response->getParsedResponse();

            // is there a special action to be done?
            if (isset($responses[$responseCode])) {
                return call_user_func($responses[$responseCode], $response);
            }

            switch ((int)$responseCode) {
                case 200:
                case 201:
                case 202:
                case 409:
                    $result = $responseBody;
                    break;
                case 204:
                    $result = null;
                    break;
                default:
                    $message = $this->getExceptionMessage($responseBody, $responseCode);
                    throw new RestClientAttemptException($message);
            }
        } catch (\Exception $exception) {
            $errorMessage = implode(
                PHP_EOL,
                [
                    'Dotmailer REST client exception:',
                    '[exception type] ' . get_class($exception),
                    '[exception message] ' . $exception->getMessage(),
                    '[request url] ' . $requestUrl,
                    '[request method] ' . $requestMethod,
                    '[request data] ' . $requestData,
                    '[response code] ' . $responseCode,
                    '[response body] ' . $responseBody,
                ]
            );

            if ($this->isAttemptNecessary($responseCode)) {
                $this->logAttempt($errorMessage);
                $result = $this->makeNewAttempt($paramArr, $responses);
            } else {
                $this->resetAttemptCount();

                throw new RestClientException($errorMessage, 0, $exception);
            }
        }

        $this->resetAttemptCount();

        return $result;
    }

    /**
     * @param string $responseBodyString
     * @param string|null $returnCode
     * @return string
     */
    protected function getExceptionMessage($responseBodyString, $returnCode = null)
    {
        $decoded = json_decode($responseBodyString, true);
        if (is_array($decoded) && isset($decoded['message'])) {
            return $decoded['message'];
        }
        switch ((int)$returnCode) {
            case 404:
                return 'NOT FOUND';
            default:
                return 'Unexpected response';
        }
    }

    /**
     * @param int $responseCode
     * @return bool
     */
    protected function isAttemptNecessary($responseCode)
    {
        return
            !in_array($responseCode, [401, 404]) &&
            $this->multipleAttemptsEnabled &&
            ($this->attempted <= count($this->sleepBetweenAttempt) - 1);
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
     * @param array|string $paramArr
     * @param array        $responses
     *
     * @return string|null
     */
    protected function makeNewAttempt($paramArr, $responses = [])
    {
        sleep((int)$this->getSleepBetweenAttempt());
        ++$this->attempted;

        return $this->execute($paramArr, $responses);
    }

    /**
     * Log attempt
     *
     * @param string $errorMessage
     */
    protected function logAttempt($errorMessage)
    {
        if (!empty($this->logger)) {
            $this->logger->warning(
                '[Warning] Attempt failed. Error message:' . PHP_EOL . $errorMessage
            );
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
            return $this->sleepBetweenAttempt[$this->attempted];
        }

        return end($this->sleepBetweenAttempt);
    }
}
