<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Exception\InvalidDefaultValueException;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Form\Handler\DataFieldFormHandler;
use Oro\Bundle\DotmailerBundle\Model\DataFieldManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class DataFieldFormHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $managerRegistry;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var DataFieldManager|\PHPUnit\Framework\MockObject\MockObject */
    private $dataFieldManager;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var DataField */
    private $entity;

    /** @var DataFieldFormHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->form = $this->createMock(Form::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->dataFieldManager = $this->createMock(DataFieldManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entity = new DataField();

        $this->handler = new DataFieldFormHandler(
            $this->form,
            $this->managerRegistry,
            $this->logger,
            $this->translator,
            $this->dataFieldManager
        );
    }

    public function testProcessUnsupportedRequest()
    {
        $request = new Request();

        $this->form->expects($this->never())
            ->method('handleRequest');

        $this->assertFalse($this->handler->process($request));
    }

    public function testProcessValidFormWithDMFieldCreated()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $this->form->expects($this->any())
            ->method('getData')
            ->willReturn($this->entity);
        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $this->form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->dataFieldManager->expects($this->once())
            ->method('createOriginDataField')
            ->with($this->entity);

        $em = $this->createMock(EntityManager::class);
        $this->managerRegistry->expects($this->any())
            ->method('getManager')
            ->willReturn($em);
        $em->expects($this->once())
            ->method('persist')
            ->with($this->entity);
        $em->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($request));
    }

    public function testProcessFormWithInvalidDefaultValueException()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $this->form->expects($this->any())
            ->method('getData')
            ->willReturn($this->entity);
        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $this->form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->dataFieldManager->expects($this->once())
            ->method('createOriginDataField')
            ->with($this->entity)
            ->willThrowException(new InvalidDefaultValueException('Invalid Default Value'));
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.dotmailer.handler.default_value_not_match')
            ->willReturn('Translated Default Value Error.');

        $this->form->expects(self::once())
            ->method('addError')
            ->with(self::callback(function (FormError $error) {
                return 'Translated Default Value Error. Invalid Default Value' === $error->getMessage();
            }));

        $this->managerRegistry->expects($this->never())
            ->method('getManager');

        $this->assertFalse($this->handler->process($request));
    }

    public function testProcessFormWithRestClientException()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $this->form->expects($this->any())
            ->method('getData')
            ->willReturn($this->entity);
        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $this->form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->dataFieldManager->expects($this->once())
            ->method('createOriginDataField')
            ->with($this->entity)
            ->willThrowException(new RestClientException('', 0, new \Exception('Dotmailer Exception Message')));
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.dotmailer.handler.unable_to_create_field')
            ->willReturn('Translated Default Value Error.');

        $this->form->expects(self::once())
            ->method('addError')
            ->with(self::callback(function (FormError $error) {
                return 'Translated Default Value Error. Dotmailer Exception Message' === $error->getMessage();
            }));

        $this->managerRegistry->expects($this->never())
            ->method('getManager');

        $this->assertFalse($this->handler->process($request));
    }

    public function testProcessFormWithException()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);

        $this->form->expects($this->any())
            ->method('getData')
            ->willReturn($this->entity);
        $this->form->expects($this->once())
            ->method('handleRequest')
            ->with($request);
        $this->form->expects($this->once())
            ->method('isSubmitted')
            ->willReturn(true);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->dataFieldManager->expects($this->once())
            ->method('createOriginDataField')
            ->with($this->entity)
            ->willThrowException(new \Exception());
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.dotmailer.handler.unable_to_create_field')
            ->willReturn('Translated Default Value Error.');
        $this->form->expects(self::once())
            ->method('addError')
            ->with(self::callback(function (FormError $error) {
                return 'Translated Default Value Error.' === $error->getMessage();
            }));

        $this->logger->expects($this->once())
            ->method('error');

        $this->managerRegistry->expects($this->never())
            ->method('getManager');

        $this->assertFalse($this->handler->process($request));
    }

    public function testProcessFormWithUpdateMarker()
    {
        $request = new Request();
        $request->setMethod(Request::METHOD_POST);
        $request->attributes->add(
            [
                DataFieldFormHandler::UPDATE_MARKER => 1
            ]
        );
        $this->form->expects($this->any())
            ->method('getData')
            ->willReturn($this->entity);
        $this->form->expects($this->once())
            ->method('handleRequest');

        $this->dataFieldManager->expects($this->never())
            ->method('createOriginDataField');

        $this->assertFalse($this->handler->process($request));
    }
}
