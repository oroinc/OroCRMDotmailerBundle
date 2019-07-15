<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Handler;

use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Exception\InvalidDefaultValueException;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;
use Oro\Bundle\DotmailerBundle\Form\Handler\DataFieldFormHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class DataFieldFormHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $managerRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $dataFieldManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var DataFieldFormHandler */
    protected $handler;

    /** @var DataField */
    protected $entity;

    protected function setUp()
    {
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()->getMock();
        $this->managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Contracts\Translation\TranslatorInterface')
            ->disableOriginalConstructor()->getMock();
        $this->dataFieldManager = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Model\DataFieldManager')
            ->disableOriginalConstructor()->getMock();
        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()->getMock();
        $this->entity  = new DataField();
        $this->handler = new DataFieldFormHandler(
            $this->form,
            $this->managerRegistry,
            $requestStack,
            $this->logger,
            $this->translator,
            $this->dataFieldManager
        );
    }

    public function testProcessUnsupportedRequest()
    {
        $this->form->expects($this->once())->method('setData')
            ->with($this->entity);

        $this->form->expects($this->never())->method('handleRequest');

        $this->assertFalse($this->handler->process($this->entity));
    }

    public function testProcessValidFormWithDMFieldCreated()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once()) ->method('handleRequest') ->with($this->request);
        $this->form->expects($this->once()) ->method('isSubmitted')
            ->will($this->returnValue(true));
        $this->form->expects($this->once()) ->method('isValid')
            ->will($this->returnValue(true));

        $this->dataFieldManager->expects($this->once())->method('createOriginDataField')->with($this->entity);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $this->managerRegistry->expects($this->any())->method('getManager')->will($this->returnValue($em));
        $em->expects($this->once()) ->method('persist') ->with($this->entity);
        $em->expects($this->once())->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }

    public function testProcessFormWithInvalidDefaultValueException()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once()) ->method('handleRequest') ->with($this->request);
        $this->form->expects($this->once()) ->method('isSubmitted')
            ->will($this->returnValue(true));
        $this->form->expects($this->once()) ->method('isValid')
            ->will($this->returnValue(true));

        $this->dataFieldManager->expects($this->once())->method('createOriginDataField')->with($this->entity)
            ->will($this->throwException(new InvalidDefaultValueException('Invalid Default Value')));
        $this->translator->expects($this->once())->method('trans')
            ->with('oro.dotmailer.handler.default_value_not_match')
            ->will($this->returnValue('Translated Default Value Error.'));

        $this->form->expects($this->once())->method('addError')
            ->with($this->attributeEqualTo('message', 'Translated Default Value Error. Invalid Default Value'));

        $this->managerRegistry->expects($this->never())->method('getManager');

        $this->assertFalse($this->handler->process($this->entity));
    }

    public function testProcessFormWithRestClientException()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once()) ->method('handleRequest') ->with($this->request);
        $this->form->expects($this->once()) ->method('isSubmitted')
            ->will($this->returnValue(true));
        $this->form->expects($this->once()) ->method('isValid')
            ->will($this->returnValue(true));

        $this->dataFieldManager->expects($this->once())->method('createOriginDataField')->with($this->entity)
            ->will($this->throwException(new RestClientException(
                '',
                0,
                new \Exception('Dotmailer Exception Message')
            )));
        $this->translator->expects($this->once())->method('trans')
            ->with('oro.dotmailer.handler.unable_to_create_field')
            ->will($this->returnValue('Translated Default Value Error.'));

        $this->form->expects($this->once())->method('addError')
            ->with($this->attributeEqualTo('message', 'Translated Default Value Error. Dotmailer Exception Message'));

        $this->managerRegistry->expects($this->never())->method('getManager');

        $this->assertFalse($this->handler->process($this->entity));
    }

    public function testProcessFormWithException()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once()) ->method('handleRequest') ->with($this->request);
        $this->form->expects($this->once()) ->method('isSubmitted')
            ->will($this->returnValue(true));
        $this->form->expects($this->once()) ->method('isValid')
            ->will($this->returnValue(true));

        $this->dataFieldManager->expects($this->once())->method('createOriginDataField')->with($this->entity)
            ->will($this->throwException(new \Exception()));
        $this->translator->expects($this->once())->method('trans')
            ->with('oro.dotmailer.handler.unable_to_create_field')
            ->will($this->returnValue('Translated Default Value Error.'));
        $this->form->expects($this->once())->method('addError')
            ->with($this->attributeEqualTo('message', 'Translated Default Value Error.'));

        $this->logger->expects($this->once())->method('error');

        $this->managerRegistry->expects($this->never())->method('getManager');

        $this->assertFalse($this->handler->process($this->entity));
    }

    public function testProcessFormWithUpdateMarker()
    {
        $this->request->setMethod('POST');
        $this->request->attributes->add(
            [
                DataFieldFormHandler::UPDATE_MARKER => 1
            ]
        );
        $this->form->expects($this->once())->method('setData')
            ->with($this->entity);

        $this->form->expects($this->once())->method('handleRequest');

        $this->dataFieldManager->expects($this->never())->method('createOriginDataField');

        $this->assertFalse($this->handler->process($this->entity));
    }
}
