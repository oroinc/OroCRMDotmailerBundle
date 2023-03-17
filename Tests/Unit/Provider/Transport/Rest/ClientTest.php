<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Rest;

use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Provider\Transport\Rest\Client;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Psr\Log\LoggerInterface;
use RestClient\Request;
use RestClient\Response;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    /** @var Response|\PHPUnit\Framework\MockObject\MockObject */
    private $response;

    /** @var \stdClass */
    private $info;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var Client */
    private $client;

    protected function setUp(): void
    {
        $this->response = $this->createMock(Response::class);
        $this->info = new \stdClass();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client = new Client('username', 'password');
        $this->client->setLogger($this->logger);
    }

    private function initClient(): void
    {
        $restClient = $this->createMock(\RestClient\Client::class);

        ReflectionUtil::setPropertyValue($this->client, 'restClient', $restClient);

        $request = $this->createMock(Request::class);

        $restClient->expects($this->once())
            ->method('newRequest')
            ->willReturn($request);
        $request->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->response);
        $this->response->expects($this->once())
            ->method('getInfo')
            ->willReturn($this->info);
    }

    /**
     * @dataProvider httpCodeDataProvider
     */
    public function testExecuteOk($code)
    {
        $this->initClient();

        $result = 'Ok';
        $this->response->expects($this->once())
            ->method('getParsedResponse')
            ->willReturn($result);

        $this->info->http_code = $code;

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
        $this->initClient();

        $this->response->expects($this->once())
            ->method('getParsedResponse');

        $this->info->http_code = 204;

        $this->assertNull($this->client->execute('testCall'));
    }

    public function testExecuteSpecialFunction()
    {
        $this->initClient();

        $result = 'Ok';
        $this->response->expects($this->exactly(2))
            ->method('getParsedResponse')
            ->willReturn($result);

        $this->info->http_code = 301;
        $params = [301 => [$this->response, 'getParsedResponse']];

        $this->assertEquals($result, $this->client->execute('testCall', $params));
    }

    /**
     * @dataProvider executeAttemptsFailedDataProvider
     */
    public function testExecuteAttemptsFailed(string $responseBody, int $responseCode, string $expectedMessage)
    {
        $exceptionMessage = 'Dotmailer REST client exception:' . PHP_EOL .
            '[exception type] Oro\Bundle\DotmailerBundle\Exception\RestClientAttemptException' . PHP_EOL .
            '[exception message] ' . $expectedMessage . PHP_EOL .
            '[request url] testCall' . PHP_EOL .
            '[request method] ' . PHP_EOL .
            '[request data] ' . PHP_EOL .
            '[response code] ' . $responseCode . PHP_EOL .
            '[response body] ' . $responseBody;

        $this->expectException(RestClientException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $restClient = $this->createMock(\RestClient\Client::class);

        ReflectionUtil::setPropertyValue($this->client, 'restClient', $restClient);
        ReflectionUtil::setPropertyValue($this->client, 'sleepBetweenAttempt', [0.1, 0.2, 0.3, 0.4]);

        $request = $this->createMock(Request::class);

        $restClient->expects($this->exactly(5))
            ->method('newRequest')
            ->willReturn($request);

        $request->expects($this->exactly(5))
            ->method('getResponse')
            ->willReturn($this->response);

        $this->response->expects($this->exactly(5))
            ->method('getInfo')
            ->willReturn($this->info);

        $this->info->http_code = $responseCode;

        $this->response->expects($this->exactly(5))
            ->method('getParsedResponse')
            ->willReturn($responseBody);

        $this->logger->expects($this->exactly(8))
            ->method('warning')
            ->withConsecutive(
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $exceptionMessage],
                ['[Warning] Attempt number 1 with 0.1 sec delay.'],
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $exceptionMessage],
                ['[Warning] Attempt number 2 with 0.2 sec delay.'],
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $exceptionMessage],
                ['[Warning] Attempt number 3 with 0.3 sec delay.'],
                ['[Warning] Attempt failed. Error message:' . PHP_EOL . $exceptionMessage],
                ['[Warning] Attempt number 4 with 0.4 sec delay.']
            );

        $this->client->execute('testCall');
    }

    public function executeAttemptsFailedDataProvider(): array
    {
        return [
            [
                'response_body' => '{"message": "Some error"}',
                'response_code' => 500,
                'expected_message' => 'Some error'
            ],
            [
                'response_body' => 'Some error',
                'response_code' => 500,
                'expected_message' => 'Unexpected response'
            ],
            [
                'response_body' => '{"error":"Some error"}',
                'response_code' => 500,
                'expected_message' => 'Unexpected response'
            ]
        ];
    }

    public function testExecuteAttemptsPassed()
    {
        $restClient = $this->createMock(\RestClient\Client::class);

        ReflectionUtil::setPropertyValue($this->client, 'restClient', $restClient);
        ReflectionUtil::setPropertyValue($this->client, 'sleepBetweenAttempt', [0.1, 0.2, 0.3, 0.4]);

        $request = $this->createMock(Request::class);

        $exceptionMessagePattern = 'Dotmailer REST client exception:' . PHP_EOL .
            '[exception type] Exception' . PHP_EOL .
            '[exception message] %s' . PHP_EOL .
            '[request url] testCall' . PHP_EOL .
            '[request method] ' . PHP_EOL .
            '[request data] ' . PHP_EOL .
            '[response code] ' . PHP_EOL .
            '[response body] ';

        $this->logger->expects($this->exactly(6))
            ->method('warning')
            ->withConsecutive(
                [
                    '[Warning] Attempt failed. Error message:' . PHP_EOL .
                    sprintf($exceptionMessagePattern, 'Exception A')
                ],
                ['[Warning] Attempt number 1 with 0.1 sec delay.'],
                [
                    '[Warning] Attempt failed. Error message:' . PHP_EOL .
                    sprintf($exceptionMessagePattern, 'Exception B')
                ],
                ['[Warning] Attempt number 2 with 0.2 sec delay.'],
                [
                    '[Warning] Attempt failed. Error message:' . PHP_EOL .
                    sprintf($exceptionMessagePattern, 'Exception C')
                ],
                ['[Warning] Attempt number 3 with 0.3 sec delay.']
            );

        $restClient->expects($this->exactly(4))
            ->method('newRequest')
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function () {
                    throw new \Exception('Exception A');
                }),
                new ReturnCallback(function () {
                    throw new \Exception('Exception B');
                }),
                new ReturnCallback(function () {
                    throw new \Exception('Exception C');
                }),
                $request
            );

        $request->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->response);

        $this->response->expects($this->once())
            ->method('getInfo')
            ->willReturn($this->info);

        $this->info->http_code = 200;

        $expectedResult = 'Some result';
        $this->response->expects($this->once())
            ->method('getParsedResponse')
            ->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->client->execute('testCall'));
    }
}
