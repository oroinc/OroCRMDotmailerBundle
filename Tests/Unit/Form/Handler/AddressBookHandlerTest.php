<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use DotMailer\Api\DataTypes\JsonObject;
use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Entity\DotmailerTransport as Transport;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Form\Handler\AddressBookHandler;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddressBookHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FORM_DATA = ['field' => 'value'];

    /**
     * @var FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $form;

    /**
     * @var Request|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $request;

    /**
     * @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $manager;

    /**
     * @var DotmailerTransport|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $transport;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $logger;

    /**
     * @var AddressBook|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entity;

    /**
     * @var AddressBookHandler
     */
    protected $handler;

    protected function setUp(): void
    {
        $this->form = $this->getMockBuilder(FormInterface::class)->getMock();
        $this->request = new Request();
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

    protected function tearDown(): void
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

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);

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
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        /** @var Channel|\PHPUnit\Framework\MockObject\MockObject $channel **/
        $channel = $this->getMockBuilder(Channel::class)->getMock();
        /** @var Transport|\PHPUnit\Framework\MockObject\MockObject $transport **/
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
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['visibility', $visibilityForm]
            ]);
        /** @var JsonObject|\PHPUnit\Framework\MockObject\MockObject $apiAddressBook **/
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
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        /** @var Channel|\PHPUnit\Framework\MockObject\MockObject $channel **/
        $channel = $this->getMockBuilder(Channel::class)->getMock();
        /** @var Transport|\PHPUnit\Framework\MockObject\MockObject $transport **/
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
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['visibility', $visibilityForm]
            ]);
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
        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('POST');
        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        /** @var Channel|\PHPUnit\Framework\MockObject\MockObject $channel **/
        $channel = $this->getMockBuilder(Channel::class)->getMock();
        /** @var Transport|\PHPUnit\Framework\MockObject\MockObject $transport **/
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
