<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DotmailerBundle\Form\Handler\DataFieldFormHandler;
use Oro\Bundle\DotmailerBundle\Entity\DataField;
use Oro\Bundle\DotmailerBundle\Exception\InvalidDefaultValueException;
use Oro\Bundle\DotmailerBundle\Exception\RestClientException;

class DataFieldFormHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $managerRegistry;

     /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dataFieldManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var DataFieldFormHandler */
    protected $handler;

    /** @var DataField */
    protected $entity;

    protected function setUp()
    {
        $this->request = new Request();
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()->getMock();
        $this->managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()->getMock();
        $this->dataFieldManager = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Model\DataFieldManager')
            ->disableOriginalConstructor()->getMock();
        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()->getMock();
        $this->entity  = new DataField();
        $this->handler = new DataFieldFormHandler(
            $this->form,
            $this->managerRegistry,
            $this->request,
            $this->logger,
            $this->translator,
            $this->dataFieldManager
        );
    }

    public function testProcessUnsupportedRequest()
    {
        $this->form->expects($this->once())->method('setData')
            ->with($this->entity);

        $this->form->expects($this->never())->method('submit');

        $this->assertFalse($this->handler->process($this->entity));
    }

    public function testProcessValidFormWithDMFieldCreated()
    {
        $this->request->setMethod('POST');

        $this->form->expects($this->once())->method('setData')->with($this->entity);
        $this->form->expects($this->once()) ->method('submit') ->with($this->request);
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
        $this->form->expects($this->once()) ->method('submit') ->with($this->request);
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
        $this->form->expects($this->once()) ->method('submit') ->with($this->request);
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
        $this->form->expects($this->once()) ->method('submit') ->with($this->request);
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

        $this->form->expects($this->once())->method('submit');

        $this->dataFieldManager->expects($this->never())->method('createOriginDataField');

        $this->assertFalse($this->handler->process($this->entity));
    }
}
