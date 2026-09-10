<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Rest;

use Oro\Bundle\DotmailerBundle\Exception\RestClientAttemptException;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Rest\Client;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ClientTest extends TestCase
{
    private const DEFAULT_URL = 'https://api.dotmailer.com/v2/testCall';

    private ResponseInterface|MockObject $response;

    private HttpClientInterface|MockObject $httpClient;

    private LoggerInterface|MockObject $logger;

    private Client $client;

    #[\Override]
    protected function setUp(): void
    {
        $this->response = $this->createMock(ResponseInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client = new Client('username', 'password');
        ReflectionUtil::setPropertyValue($this->client, 'httpClient', $this->httpClient);
        $this->client->setLogger($this->logger);
    }

    private function initClient(int $statusCode, string $body = ''): void
    {
        $this->httpClient->expects(self::once())
            ->method('request')
            ->with(Request::METHOD_GET, self::DEFAULT_URL, [])
            ->willReturn($this->response);
        $this->response->expects(self::once())
            ->method('getStatusCode')
            ->willReturn($statusCode);
        $this->response->expects(self::once())
            ->method('getContent')
            ->with(false)
            ->willReturn($body);
    }

    public function testExecuteBuildsRequestFromParams()
    {
        $this->client->setBaseUrl('https://r1-api.dotmailer.com/v2');

        $requestData = '{"name":"Test address book"}';
        $this->httpClient->expects(self::once())
            ->method('request')
            ->with(
                Request::METHOD_POST,
                'https://r1-api.dotmailer.com/v2/address-books',
                ['body' => $requestData]
            )
            ->willReturn($this->response);
        $this->response->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(Response::HTTP_CREATED);
        $this->response->expects(self::once())
            ->method('getContent')
            ->with(false)
            ->willReturn('{"id":1}');

        $this->assertEquals(
            '{"id":1}',
            $this->client->execute(['/address-books', Request::METHOD_POST, $requestData])
        );
    }

    /**
     * @dataProvider httpCodeDataProvider
     */
    public function testExecuteOk($code)
    {
        $result = 'Ok';
        $this->initClient($code, $result);

        $this->assertEquals($result, $this->client->execute('testCall'));
    }

    public function httpCodeDataProvider(): array
    {
        return [
            [200],
            [201],
            [202],
            [409]
        ];
    }

    public function testExecute204()
    {
        $this->initClient(204);

        $this->assertNull($this->client->execute('testCall'));
    }

    public function testExecuteSpecialFunction()
    {
        $this->httpClient->expects(self::once())
            ->method('request')
            ->willReturn($this->response);
        $this->response->expects(self::once())
            ->method('getStatusCode')
            ->willReturn(301);
        $this->response->expects(self::exactly(2))
            ->method('getContent')
            ->willReturn('Ok');

        $params = [301 => static fn (ResponseInterface $r) => $r->getContent()];

        $this->assertEquals('Ok', $this->client->execute('testCall', $params));
    }

    /**
     * @dataProvider executeAttemptsFailedDataProvider
     */
    public function testExecuteAttemptsFailed(int $responseCode, string $exceptionMessage)
    {
        $thrownException = new \RuntimeException($exceptionMessage);

        $errorMessage = 'Dotmailer REST client exception:' . PHP_EOL .
            '[exception type] RuntimeException' . PHP_EOL .
            '[exception message] ' . $exceptionMessage . PHP_EOL .
            '[request url] testCall' . PHP_EOL .
            '[request method] GET' . PHP_EOL .
            '[request data] ' . PHP_EOL .
            '[response code] ' . $responseCode . PHP_EOL .
            '[response body] ';

        $this->expectException(RestClientException::class);
        $this->expectExceptionMessage($errorMessage);

        ReflectionUtil::setPropertyValue($this->client, 'sleepBetweenAttempt', [0, 0, 0, 0]);

        $this->httpClient->expects(self::exactly(5))
            ->method('request')
            ->willReturn($this->response);

        $this->response->expects(self::exactly(5))
            ->method('getStatusCode')
            ->willReturn($responseCode);

        $this->response->expects(self::exactly(5))
            ->method('getContent')
            ->willThrowException($thrownException);

        $this->logger->expects(self::exactly(8))
            ->method('warning')
            ->withConsecutive(
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $errorMessage],
                ['[Warning] Attempt number 1 with 0 sec delay.'],
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $errorMessage],
                ['[Warning] Attempt number 2 with 0 sec delay.'],
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $errorMessage],
                ['[Warning] Attempt number 3 with 0 sec delay.'],
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $errorMessage],
                ['[Warning] Attempt number 4 with 0 sec delay.']
            );

        $this->client->execute('testCall');
    }

    public function executeAttemptsFailedDataProvider(): array
    {
        return [
            [500, 'Internal Server Error'],
            [503, 'Service Unavailable'],
        ];
    }

    /**
     * A transport level failure - an unreachable proxy, a refused connection, a TLS or DNS error - leaves
     * the response code unset. The attempts must still be made and the failure must surface as
     * a RestClientException.
     */
    public function testExecuteTransportFailure()
    {
        ReflectionUtil::setPropertyValue($this->client, 'sleepBetweenAttempt', [0, 0, 0, 0]);

        $exceptionMessage = 'Failed to connect to proxy: Connection refused';
        $errorMessage = 'Dotmailer REST client exception:' . PHP_EOL .
            '[exception type] ' . TransportException::class . PHP_EOL .
            '[exception message] ' . $exceptionMessage . PHP_EOL .
            '[request url] testCall' . PHP_EOL .
            '[request method] GET' . PHP_EOL .
            '[request data] ' . PHP_EOL .
            '[response code] ' . PHP_EOL .
            '[response body] ';

        $this->httpClient->expects(self::exactly(5))
            ->method('request')
            ->with(Request::METHOD_GET, self::DEFAULT_URL, [])
            ->willThrowException(new TransportException($exceptionMessage));

        $this->response->expects(self::never())
            ->method('getStatusCode');

        $this->logger->expects(self::exactly(8))
            ->method('warning')
            ->withConsecutive(
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $errorMessage],
                ['[Warning] Attempt number 1 with 0 sec delay.'],
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $errorMessage],
                ['[Warning] Attempt number 2 with 0 sec delay.'],
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $errorMessage],
                ['[Warning] Attempt number 3 with 0 sec delay.'],
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $errorMessage],
                ['[Warning] Attempt number 4 with 0 sec delay.']
            );

        $this->expectException(RestClientException::class);
        $this->expectExceptionMessage($errorMessage);

        $this->client->execute('testCall');
    }

    public function testExecuteAttemptsPassed()
    {
        ReflectionUtil::setPropertyValue($this->client, 'sleepBetweenAttempt', [0, 0, 0, 0]);

        $exceptionMessagePattern = 'Dotmailer REST client exception:' . PHP_EOL .
            '[exception type] %s' . PHP_EOL .
            '[exception message] Unexpected response' . PHP_EOL .
            '[request url] testCall' . PHP_EOL .
            '[request method] GET' . PHP_EOL .
            '[request data] ' . PHP_EOL .
            '[response code] 500' . PHP_EOL .
            '[response body] ';

        $this->logger->expects(self::exactly(8))
            ->method('warning')
            ->withConsecutive(
                [
                    '[Warning] Attempt failed. Error message:' . PHP_EOL .
                    sprintf($exceptionMessagePattern, RestClientAttemptException::class),
                ],
                ['[Warning] Attempt number 1 with 0 sec delay.'],
                [
                    '[Warning] Attempt failed. Error message:' . PHP_EOL .
                    sprintf($exceptionMessagePattern, RestClientAttemptException::class),
                ],
                ['[Warning] Attempt number 2 with 0 sec delay.'],
                [
                    '[Warning] Attempt failed. Error message:' . PHP_EOL .
                    sprintf($exceptionMessagePattern, RestClientAttemptException::class),
                ],
                ['[Warning] Attempt number 3 with 0 sec delay.'],
                [
                    '[Warning] Attempt failed. Error message:' . PHP_EOL .
                    sprintf($exceptionMessagePattern, RestClientAttemptException::class),
                ],
                ['[Warning] Attempt number 4 with 0 sec delay.'],
            );

        $expectedResult = 'Expected content';
        $this->response->expects(self::exactly(5))
            ->method('getStatusCode')
            ->willReturnOnConsecutiveCalls(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                Response::HTTP_INTERNAL_SERVER_ERROR,
                Response::HTTP_INTERNAL_SERVER_ERROR,
                Response::HTTP_INTERNAL_SERVER_ERROR,
                Response::HTTP_OK
            );
        $this->response->expects(self::exactly(5))
            ->method('getContent')
            ->willReturnOnConsecutiveCalls('', '', '', '', $expectedResult);

        $this->httpClient->expects(self::exactly(5))
            ->method('request')
            ->willReturn($this->response);

        $this->assertEquals($expectedResult, $this->client->execute('testCall'));
    }

    /**
     * @dataProvider executeExceptionMessageDataProvider
     */
    public function testExecuteExceptionMessage(int $responseCode, string $responseBody, string $expectedMessage): void
    {
        $this->httpClient->expects(self::once())
            ->method('request')
            ->willReturn($this->response);
        $this->response->expects(self::once())
            ->method('getStatusCode')
            ->willReturn($responseCode);
        $this->response->expects(self::once())
            ->method('getContent')
            ->with(false)
            ->willReturn($responseBody);

        $errorMessage = 'Dotmailer REST client exception:' . PHP_EOL .
            '[exception type] Oro\Bundle\DotmailerBundle\Exception\RestClientAttemptException' . PHP_EOL .
            '[exception message] ' . $expectedMessage . PHP_EOL .
            '[request url] testCall' . PHP_EOL .
            '[request method] GET' . PHP_EOL .
            '[request data] ' . PHP_EOL .
            '[response code] ' . $responseCode . PHP_EOL .
            '[response body] ' . $responseBody;

        $this->expectException(RestClientException::class);
        $this->expectExceptionMessage($errorMessage);

        $this->client->execute('testCall');
    }

    public function executeExceptionMessageDataProvider(): array
    {
        return [
            'JSON body with message key' => [401, '{"message": "Custom error"}', 'Custom error'],
            'JSON body without message key' => [401, '{"error": "Custom error"}', 'Unexpected response'],
            'non-JSON body' => [401, 'Some text', 'Unexpected response'],
            'non-JSON body with 404 code' => [404, 'Some text', 'Not Found'],
        ];
    }
}
