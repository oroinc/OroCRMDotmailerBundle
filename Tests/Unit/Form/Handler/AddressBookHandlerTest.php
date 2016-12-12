<?php

namespace Oro\Bundle\MarketingListBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Psr\Log\LoggerInterface;
use DotMailer\Api\DataTypes\ApiAddressBook;

use Oro\Bundle\DotmailerBundle\Entity\AddressBook;
use Oro\Bundle\DotmailerBundle\Form\Handler\AddressBookHandler;
use Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\DotmailerBundle\Entity\DotmailerTransport as Transport;

class AddressBookHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Form
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DotmailerTransport
     */
    protected $transport;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected $logger;

    /**
     * @var AddressBook
     */
    protected $entity;

    /**
     * @var AddressBookHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = new Request();
        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->transport = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Provider\Transport\DotmailerTransport')
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')->getMock();
        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')->getMock();

        $this->entity = new AddressBook();
        $this->handler = new AddressBookHandler(
            $this->form,
            $this->request,
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

        $this->request->setMethod($method);

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
        $channel = new Channel();
        $channel->setTransport(new Transport());
        $this->entity->setChannel($channel);

        $this->request->setMethod('POST');

        $this->form->expects($this->once())
            ->method('setData')
            ->with($this->entity);
        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->transport->expects($this->once())
            ->method('init')
            ->with($this->entity->getChannel()->getTransport());

        $nameForm = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $nameForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('Test'));
        $this->form->expects($this->at(3))
            ->method('get')
            ->with('name')
            ->will($this->returnValue($nameForm));
        $visibilityForm = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();
        $visibilityForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue('Private'));
        $this->form->expects($this->at(4))
            ->method('get')
            ->with('visibility')
            ->will($this->returnValue($visibilityForm));
        $apiAddressBook = new ApiAddressBook();
        $apiAddressBook->offsetSet('id', 1);
        $this->transport->expects($this->once())
            ->method('createAddressBook')
            ->will($this->returnValue($apiAddressBook));

        $this->manager->expects($this->once())
            ->method('persist')
            ->with($this->entity);
        $this->manager->expects($this->once())
            ->method('flush');

        $this->assertTrue($this->handler->process($this->entity));
    }
}
