<?php

namespace OroCRM\Bundle\DotmailerBundle\Tests\Unit\Placeholder;

use OroCRM\Bundle\DotmailerBundle\Provider\Transport\Rest\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client
     */
    protected $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $response;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $info;

    protected function setUp()
    {
        $this->target = new Client('username', 'password');
    }

    protected function initTarget()
    {
        $restClient = $this->getMock('\RestClient\Client');

        $class = new \ReflectionClass($this->target);
        $prop  = $class->getProperty('restClient');
        $prop->setAccessible(true);
        $prop->setValue($this->target, $restClient);

        $request = $this->getMock('\RestClient\Request');
        $this->response = $this->getMockBuilder('\RestClient\Response')->disableOriginalConstructor()->getMock();
        $this->info = new \stdClass();

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
        $this->initTarget();

        $result = 'Ok';
        $this->response->expects($this->once())
            ->method('getParsedResponse')
            ->will($this->returnValue($result));

        $this->info->http_code = $code;

        $this->assertEquals($result, $this->target->execute('testCall'));
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
        ];
    }

    public function testExecute204()
    {
        $this->initTarget();

        $this->response->expects($this->at(0))
            ->method('getParsedResponse');

        $this->info->http_code = 204;

        $this->assertNull($this->target->execute('testCall'));
    }

    public function testExecuteSpecialFunction()
    {
        $this->initTarget();

        $result = 'Ok';
        $this->response->expects($this->once())
            ->method('getParsedResponse')
            ->will($this->returnValue($result));

        $this->info->http_code = 301;
        $params = [301 => [$this->response, 'getParsedResponse']];

        $this->assertEquals($result, $this->target->execute('testCall', $params));
    }

    /**
     * @expectedException \OroCRM\Bundle\DotmailerBundle\Exception\RestClientException
     * @expectedExceptionMessage Response HTTP CODE: 500, Body: Internal Error
     */
    public function testExecuteException()
    {
        $restClient = $this->getMock('\RestClient\Client');

        $class = new \ReflectionClass($this->target);
        $prop  = $class->getProperty('restClient');
        $prop->setAccessible(true);
        $prop->setValue($this->target, $restClient);
        $prop  = $class->getProperty('sleepBetweenAttempt');
        $prop->setAccessible(true);
        $prop->setValue($this->target, [0.1,0.1,0.1,0.1]);

        $request = $this->getMock('\RestClient\Request');
        $this->response = $this->getMockBuilder('\RestClient\Response')->disableOriginalConstructor()->getMock();
        $this->info = new \stdClass();

        $restClient->expects($this->exactly(4))
            ->method('newRequest')
            ->will($this->returnValue($request));
        $request->expects($this->exactly(4))
            ->method('getResponse')
            ->will($this->returnValue($this->response));
        $this->response->expects($this->exactly(4))
            ->method('getInfo')
            ->will($this->returnValue($this->info));

        $result = 'Internal Error';
        $this->response->expects($this->exactly(4))
            ->method('getParsedResponse')
            ->will($this->returnValue($result));

        $this->info->http_code = 500;

        $this->target->execute('testCall');
    }
}
