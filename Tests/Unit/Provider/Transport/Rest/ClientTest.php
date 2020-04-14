<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Provider\Transport\Rest;

use Oro\Bundle\DotmailerBundle\Provider\Transport\Rest\Client;

class ClientTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $response;

    /**
     * @var \stdClass
     */
    protected $info;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock('Psr\Log\LoggerInterface');
        $this->client = new Client('username', 'password');
        $this->client->setLogger($this->logger);
        $this->response = $this->getMockBuilder('\RestClient\Response')->disableOriginalConstructor()->getMock();
        $this->info = new \stdClass();
    }

    protected function initClient()
    {
        $restClient = $this->createMock('\RestClient\Client');

        $class = new \ReflectionClass($this->client);
        $prop  = $class->getProperty('restClient');
        $prop->setAccessible(true);
        $prop->setValue($this->client, $restClient);

        $request = $this->createMock('\RestClient\Request');

        $restClient->expects($this->once())
            ->method('newRequest')
            ->will($this->returnValue($request));
        $request->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($this->response));
        $this->response->expects($this->once())
            ->method('getInfo')
            ->will($this->returnValue($this->info));
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
            ->will($this->returnValue($result));

        $this->info->http_code = $code;

        $this->assertEquals($result, $this->client->execute('testCall'));
    }

    /**
     * @return array
     */
    public function httpCodeDataProvider()
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

        $this->response->expects($this->at(0))
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
            ->will($this->returnValue($result));

        $this->info->http_code = 301;
        $params = [301 => [$this->response, 'getParsedResponse']];

        $this->assertEquals($result, $this->client->execute('testCall', $params));
    }

    /**
     * @dataProvider executeAttemptsFailedDataProvider
     * @param string $responseBody
     * @param string $responseCode
     * @param string $expectedMessage
     */
    public function testExecuteAttemptsFailed($responseBody, $responseCode, $expectedMessage)
    {
        $exceptionMessage = 'Dotmailer REST client exception:' . PHP_EOL .
            '[exception type] Oro\Bundle\DotmailerBundle\Exception\RestClientAttemptException' . PHP_EOL .
            '[exception message] ' . $expectedMessage . PHP_EOL .
            '[request url] testCall' . PHP_EOL .
            '[request method] ' . PHP_EOL .
            '[request data] ' . PHP_EOL .
            '[response code] ' . $responseCode . PHP_EOL .
            '[response body] ' . $responseBody;

        $this->expectException(\Oro\Bundle\DotmailerBundle\Exception\RestClientException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $restClient = $this->createMock('RestClient\Client');

        $class = new \ReflectionClass($this->client);
        $prop  = $class->getProperty('restClient');
        $prop->setAccessible(true);
        $prop->setValue($this->client, $restClient);
        $prop  = $class->getProperty('sleepBetweenAttempt');
        $prop->setAccessible(true);
        $prop->setValue($this->client, [0.1, 0.2, 0.3, 0.4]);

        $request = $this->createMock('RestClient\Request');

        $restClient->expects($this->exactly(5))
            ->method('newRequest')
            ->will($this->returnValue($request));

        $request->expects($this->exactly(5))
            ->method('getResponse')
            ->will($this->returnValue($this->response));

        $this->response->expects($this->exactly(5))
            ->method('getInfo')
            ->will($this->returnValue($this->info));

        $this->info->http_code = $responseCode;

        $this->response->expects($this->exactly(5))
            ->method('getParsedResponse')
            ->will($this->returnValue($responseBody));

        $this->logger->expects($this->at(0))
            ->method('warning')
            ->with('[Warning] Attempt failed. Error message:' . PHP_EOL . $exceptionMessage);

        $this->logger->expects($this->at(1))
            ->method('warning')
            ->with('[Warning] Attempt number 1 with 0.1 sec delay.');

        $this->logger->expects($this->at(2))
            ->method('warning')
            ->with('[Warning] Attempt failed. Error message:' . PHP_EOL . $exceptionMessage);

        $this->logger->expects($this->at(3))
            ->method('warning')
            ->with('[Warning] Attempt number 2 with 0.2 sec delay.');

        $this->logger->expects($this->at(4))
            ->method('warning')
            ->with('[Warning] Attempt failed. Error message:' . PHP_EOL . $exceptionMessage);

        $this->logger->expects($this->at(5))
            ->method('warning')
            ->with('[Warning] Attempt number 3 with 0.3 sec delay.');

        $this->logger->expects($this->at(6))
            ->method('warning')
            ->with('[Warning] Attempt failed. Error message:' . PHP_EOL . $exceptionMessage);

        $this->logger->expects($this->at(7))
            ->method('warning')
            ->with('[Warning] Attempt number 4 with 0.4 sec delay.');

        $this->client->execute('testCall');
    }

    /**
     * @return array
     */
    public function executeAttemptsFailedDataProvider()
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
        $restClient = $this->createMock('RestClient\Client');

        $class = new \ReflectionClass($this->client);
        $prop  = $class->getProperty('restClient');
        $prop->setAccessible(true);
        $prop->setValue($this->client, $restClient);
        $prop  = $class->getProperty('sleepBetweenAttempt');
        $prop->setAccessible(true);
        $prop->setValue($this->client, [0.1, 0.2, 0.3, 0.4]);

        $request = $this->createMock('RestClient\Request');

        $restClient->expects($this->at(0))
            ->method('newRequest')
            ->will($this->throwException(new \Exception('Exception A')));

        $restClient->expects($this->at(1))
            ->method('newRequest')
            ->will($this->throwException(new \Exception('Exception B')));

        $restClient->expects($this->at(2))
            ->method('newRequest')
            ->will($this->throwException(new \Exception('Exception C')));

        $exceptionMessagePattern = 'Dotmailer REST client exception:' . PHP_EOL .
            '[exception type] Exception' . PHP_EOL .
            '[exception message] %s' . PHP_EOL .
            '[request url] testCall' . PHP_EOL .
            '[request method] ' . PHP_EOL .
            '[request data] ' . PHP_EOL .
            '[response code] ' . PHP_EOL .
            '[response body] ';

        $this->logger->expects($this->at(0))
            ->method('warning')
            ->with(
                '[Warning] Attempt failed. Error message:' . PHP_EOL .
                sprintf($exceptionMessagePattern, 'Exception A')
            );

        $this->logger->expects($this->at(1))
            ->method('warning')
            ->with('[Warning] Attempt number 1 with 0.1 sec delay.');

        $this->logger->expects($this->at(2))
            ->method('warning')
            ->with(
                '[Warning] Attempt failed. Error message:' . PHP_EOL .
                sprintf($exceptionMessagePattern, 'Exception B')
            );

        $this->logger->expects($this->at(3))
            ->method('warning')
            ->with('[Warning] Attempt number 2 with 0.2 sec delay.');

        $this->logger->expects($this->at(4))
            ->method('warning')
            ->with(
                '[Warning] Attempt failed. Error message:' . PHP_EOL .
                sprintf($exceptionMessagePattern, 'Exception C')
            );

        $this->logger->expects($this->at(5))
            ->method('warning')
            ->with('[Warning] Attempt number 3 with 0.3 sec delay.');

        $restClient->expects($this->at(3))
            ->method('newRequest')
            ->will($this->returnValue($request));

        $request->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue($this->response));

        $this->response->expects($this->once())
            ->method('getInfo')
            ->will($this->returnValue($this->info));

        $this->info->http_code = 200;

        $expectedResult = 'Some result';
        $this->response->expects($this->once())
            ->method('getParsedResponse')
            ->will($this->returnValue($expectedResult));

        $this->assertEquals($expectedResult, $this->client->execute('testCall'));
    }
}
