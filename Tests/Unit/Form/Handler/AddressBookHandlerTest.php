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
use Symfony\Contracts\Translation\TranslatorInterface;

class AddressBookHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var DotmailerTransport|\PHPUnit\Framework\MockObject\MockObject */
    private $transport;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    private $request;

    /** @var AddressBook|\PHPUnit\Framework\MockObject\MockObject */
    private $entity;

    /** @var AddressBookHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ObjectManager::class);
        $this->transport = $this->createMock(DotmailerTransport::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();
        $this->entity = $this->createMock(AddressBook::class);

        $this->handler = new AddressBookHandler($this->manager, $this->transport, $this->translator, $this->logger);
    }

    public function testProcessUnsupportedRequest()
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->form->expects($this->never())
            ->method('submit');

        $this->assertFalse($this->handler->process($this->entity, $this->form, $this->request));
    }

    /**
     * @dataProvider supportedMethods
     */
    public function testProcessSupportedRequest(string $method): void
    {
        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod($method);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);

        $this->assertFalse($this->handler->process($this->entity, $this->form, $this->request));
    }

    public function supportedMethods(): array
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
            ->willReturn(true);

        $channel = $this->createMock(Channel::class);
        $transport = $this->createMock(Transport::class);
        $this->entity->expects($this->once())
            ->method('getChannel')
            ->willReturn($channel);
        $channel->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);
        $this->transport->expects($this->once())
            ->method('init')
            ->with($transport);

        $this->entity->expects($this->once())
            ->method('getName')
            ->willReturn('Test address book');
        $visibilityForm = $this->createMock(FormInterface::class);
        $visibilityForm->expects($this->once())
            ->method('getData')
            ->willReturn('Public');
        $this->form->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['visibility', $visibilityForm]
            ]);
        $apiAddressBook = $this->createMock(JsonObject::class);
        $this->transport->expects($this->once())
            ->method('createAddressBook')
            ->willReturn($apiAddressBook);

        $apiAddressBook->expects($this->once())
            ->method('offsetGet')
            ->with('id')
            ->willReturn(1);
        $this->entity->expects($this->once())
            ->method('setOriginId')
            ->willReturn(1);
        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->entity);
        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity, $this->form, $this->request));
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
            ->willReturn(true);

        $channel = $this->createMock(Channel::class);
        $transport = $this->createMock(Transport::class);
        $this->entity->expects($this->once())
            ->method('getChannel')
            ->willReturn($channel);
        $channel->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);
        $this->transport->expects($this->once())
            ->method('init')
            ->with($transport);

        $this->entity->expects($this->once())
            ->method('getName')
            ->willReturn('Test address book');
        $visibilityForm = $this->createMock(FormInterface::class);
        $visibilityForm->expects($this->once())
            ->method('getData')
            ->willReturn('Public');
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

        $this->assertFalse($this->handler->process($this->entity, $this->form, $this->request));
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
            ->willReturn(true);

        $channel = $this->createMock(Channel::class);
        $transport = $this->createMock(Transport::class);
        $this->entity->expects($this->once())
            ->method('getChannel')
            ->willReturn($channel);
        $channel->expects($this->once())
            ->method('getTransport')
            ->willReturn($transport);
        $e = new \Exception('Test exception');
        $this->transport->expects($this->once())
            ->method('init')
            ->willThrowException($e);

        $this->translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn ($value) =>  $value . '_translated');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Unexpected exception occurred during creating Address Book', ['exception' => $e]);
        $this->form->expects($this->once())
            ->method('addError')
            ->with($this->isInstanceOf(FormError::class));

        $this->assertFalse($this->handler->process($this->entity, $this->form, $this->request));
    }
}
