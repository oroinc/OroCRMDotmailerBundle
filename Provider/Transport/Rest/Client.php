<?php

namespace Oro\Bundle\DotmailerBundle\Provider\Transport\Rest;

use Oro\Bundle\DotmailerBundle\Exception\RestClientAttemptException;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

    /**
     * The transport moved off romanpitak/php-rest-client, so the property no longer holds
     * a \RestClient\Client. It is kept under its old name for backward compatibility.
     *
     * @var HttpClientInterface
     */
    protected $restClient;

    private const BASE_URL = 'https://api.dotmailer.com/v2/';

    private string $baseUrl = self::BASE_URL;

    /**
     * @param string $username
     * @param string $password
     */
    public function __construct($username, $password)
    {
        // Symfony HttpClient splits the former curl budget in two: `timeout` caps the wait for any
        // progress on the connection, `max_duration` caps the whole request the way CURLOPT_TIMEOUT did.
        $this->restClient = HttpClient::create([
            'auth_basic'   => [$username, $password],
            'verify_peer'  => false,
            'timeout'      => self::CONNECT_TIMEOUT,
            'max_duration' => self::EXECUTE_TIMEOUT,
            'headers'      => ['Content-Type' => 'application/json'],
        ]);
    }

    public function setBaseUrl(string $url): void
    {
        $this->baseUrl = $url;
    }

    /**
     * @param array|string $paramArr
     * @param array $responses
     * @return string|null
     */
    public function execute($paramArr, $responses = [])
    {
        // when only url is supplied
        $paramArr = (array) $paramArr;

        [$requestUrl, $requestMethod, $requestData] = array_pad(array_values($paramArr), 3, null);
        $requestUrl = $requestUrl ?? '';
        $requestMethod = $requestMethod ?? Request::METHOD_GET;
        $responseCode = $responseBody = null;

        try {
            $url = sprintf('%s/%s', rtrim($this->baseUrl, '/'), ltrim($requestUrl, '/'));
            $options = $requestData !== null ? ['body' => $requestData] : [];

            $response = $this->restClient->request($requestMethod, $url, $options);
            $responseCode = $response->getStatusCode();
            $responseBody = $response->getContent(false);

            // is there a special action to be done?
            if (isset($responses[$responseCode])) {
                return call_user_func($responses[$responseCode], $response);
            }

            $result = $this->getResult($responseCode, $responseBody);
        } catch (\Exception $exception) {
            $errorMessage = $this->getFormattedErrorMessage(
                $exception,
                [
                    $requestUrl,
                    $requestMethod,
                    $requestData,
                    $responseCode,
                    $responseBody
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
     * @param int|null $returnCode
     * @return string
     */
    protected function getExceptionMessage($responseBodyString, $returnCode = null)
    {
        $decoded = json_decode($responseBodyString, true);
        if (is_array($decoded) && isset($decoded['message'])) {
            return $decoded['message'];
        }

        return match ($returnCode) {
            Response::HTTP_NOT_FOUND => Response::$statusTexts[Response::HTTP_NOT_FOUND],
            default => 'Unexpected response'
        };
    }

    /**
     * @param int $responseCode
     * @return bool
     */
    protected function isAttemptNecessary($responseCode)
    {
        return
            !in_array($responseCode, [Response::HTTP_UNAUTHORIZED, Response::HTTP_NOT_FOUND]) &&
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
        sleep($this->getSleepBetweenAttempt());
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

    private function getResult(int $responseCode, ?string $responseBody): ?string
    {
        return match ($responseCode) {
            Response::HTTP_OK,
            Response::HTTP_CREATED,
            Response::HTTP_ACCEPTED,
            Response::HTTP_CONFLICT => $responseBody,
            Response::HTTP_NO_CONTENT => null,
            default => throw new RestClientAttemptException($this->getExceptionMessage($responseBody, $responseCode))
        };
    }

    private function getFormattedErrorMessage(\Exception $e, array $args): string
    {
        [$requestUrl, $requestMethod, $requestData, $responseCode, $responseBody] = $args;

        return implode(
            PHP_EOL,
            [
                'Dotmailer REST client exception:',
                '[exception type] ' . get_class($e),
                '[exception message] ' . $e->getMessage(),
                '[request url] ' . $requestUrl,
                '[request method] ' . $requestMethod,
                '[request data] ' . $requestData,
                '[response code] ' . $responseCode,
                '[response body] ' . $responseBody,
            ]
        );
    }
}
