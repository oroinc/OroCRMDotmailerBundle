<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Form\Handler\AddressBookHandler;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DotmailerBundle\Entity\DotmailerTransport as Transport;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;

use DotMailer\Api\DataTypes\JsonObject;
use Psr\Log\LoggerInterface;

class AddressBookHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $form;

    /**
     * @var Request|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    /**
     * @var DotmailerTransport|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var AddressBook|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entity;

    /**
     * @var AddressBookHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->form = $this->getMockBuilder(FormInterface::class)->getMock();
        $this->request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->manager = $this->getMockBuilder(ObjectManager::class)->getMock();
        $this->transport = $this->getMockBuilder(DotmailerTransport::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $this->entity = $this->getMockBuilder(AddressBook::class)->getMock();
        $this->handler = new AddressBookHandler(
            $this->form,
            $requestStack,
            $this->manager,
            $this->transport,
            $this->translator,
            $this->logger
        );
    }

    protected function tearDown()
    {
        unset(
            $this->form,
            $this->request,
            $this->manager,
            $this->transport,
            $this->translator,
            $this->logger,
            $this->entity,
            $this->handler
        );
    }

    public function testProcessUnsupportedRequest()
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->entity));
    }

    /**
     * @dataProvider supportedMethods
     * @param string $method
     */
    public function testProcessSupportedRequest($method)
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue($method));

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);

        $this->assertFalse($this->handler->process($this->entity));
    }

    public function supportedMethods()
    {
        return [
            ['POST'],
            ['PUT']
        ];
    }

    public function testProcessValidData()
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel **/
        $channel = $this->getMockBuilder(Channel::class)->getMock();
        /** @var Transport|\PHPUnit_Framework_MockObject_MockObject $transport **/
        $transport = $this->getMockBuilder(Transport::class)->getMock();
        $this->entity->expects($this->once())
            ->method('getChannel')
            ->will($this->returnValue($channel));
        $channel->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));
        $this->transport->expects($this->once())
            ->method('init')
            ->with($transport);

        $this->entity->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Test address book'));
        $visibilityForm = $this->getMockBuilder(FormInterface::class)->getMock();
        $visibilityForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('Public'));
        $this->form->expects($this->at(3))
            ->method('get')
            ->with('visibility')
            ->will($this->returnValue($visibilityForm));
        /** @var JsonObject|\PHPUnit_Framework_MockObject_MockObject $apiAddressBook **/
        $apiAddressBook = $this->getMockBuilder(JsonObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->transport->expects($this->once())
            ->method('createAddressBook')
            ->will($this->returnValue($apiAddressBook));

        $apiAddressBook->expects($this->once())
            ->method('offsetGet')
            ->with('id')
            ->will($this->returnValue(1));
        $this->entity->expects($this->once())
            ->method('setOriginId')
            ->will($this->returnValue(1));
        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->entity);
        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    public function testProcessRestClientException()
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel **/
        $channel = $this->getMockBuilder(Channel::class)->getMock();
        /** @var Transport|\PHPUnit_Framework_MockObject_MockObject $transport **/
        $transport = $this->getMockBuilder(Transport::class)->getMock();
        $this->entity->expects($this->once())
            ->method('getChannel')
            ->will($this->returnValue($channel));
        $channel->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));
        $this->transport->expects($this->once())
            ->method('init')
            ->with($transport);

        $this->entity->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Test address book'));
        $visibilityForm = $this->getMockBuilder(FormInterface::class)->getMock();
        $visibilityForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('Public'));
        $this->form->expects($this->at(3))
            ->method('get')
            ->with('visibility')
            ->will($this->returnValue($visibilityForm));
        $this->transport->expects($this->once())
            ->method('createAddressBook')
            ->willThrowException(new RestClientException('Test rest client exception'));

        $this->form->expects($this->once())
            ->method('addError')
            ->with($this->isInstanceOf(FormError::class));

        $this->assertFalse($this->handler->process($this->entity));
    }

    public function testProcessException()
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        /** @var Channel|\PHPUnit_Framework_MockObject_MockObject $channel **/
        $channel = $this->getMockBuilder(Channel::class)->getMock();
        /** @var Transport|\PHPUnit_Framework_MockObject_MockObject $transport **/
        $transport = $this->getMockBuilder(Transport::class)->getMock();
        $this->entity->expects($this->once())
            ->method('getChannel')
            ->will($this->returnValue($channel));
        $channel->expects($this->once())
            ->method('getTransport')
            ->will($this->returnValue($transport));
        /** @var \Exception $e **/
        $e = new \Exception('Test exception');
        $this->transport->expects($this->once())
            ->method('init')
            ->willThrowException($e);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during creating Address Book', ['exception' => $e]);
        $this->form->expects($this->once())
            ->method('addError')
            ->with($this->isInstanceOf(FormError::class));

        $this->assertFalse($this->handler->process($this->entity));
    }
}
