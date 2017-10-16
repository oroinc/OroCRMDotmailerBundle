<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Processor;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\DotmailerBundle\Processor\SyncProcessor;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use Oro\Bundle\MessageQueueBundle\Test\Unit\MessageQueueExtension;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor;
use Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

class SyncProcessorTest extends \PHPUnit_Framework_TestCase
{
    use MessageQueueExtension;

    /** @var SyncProcessor */
    protected $processor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProcessorRegistry
     */
    protected $processorRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Executor
     */
    protected $jobExecutor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TypesRegistry
     */
    protected $typesRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LoggerStrategy
     */
    protected $logger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ChannelRepository
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->registry = $this->getMockForAbstractClass(
            'Doctrine\Common\Persistence\ManagerRegistry'
        );
        $this->processorRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->jobExecutor = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\ImportExport\Job\Executor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->typesRegistry = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Logger\LoggerStrategy')
            ->disableOriginalConstructor()
            ->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository = $this
            ->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getConnectedAddressBooks'])
            ->getMock();
        $this->registry->expects($this->any())
            ->method('getManager')
            ->will($this->returnValue($this->em));
        $this->registry->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new SyncProcessor(
            $this->registry,
            $this->processorRegistry,
            $this->jobExecutor,
            $this->typesRegistry,
            $this->eventDispatcher,
            $this->logger
        );

        $this->processor->setDoctrineHelper($this->doctrineHelper);
        $this->processor->setMessageProducer(self::getMessageProducer());
    }

    public function testProcessSetNewMessageQueue()
    {
        $integration = $this->getIntegration();

        $addressBook1 = $this->getMockBuilder('Oro\Bundle\DotmailerBundle\Entity\AddressBook')
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();
        $addressBook1->expects($this->atLeastOnce())
            ->method('getId')
            ->will(
                $this->returnValue(1)
            );
        $addressBooks[] = $addressBook1;

        $this->assertAddressBookCall($integration, $addressBooks);
        $this->assertReloadEntityCall($integration);

        $this->processor->process($integration);

        self::assertMessageSent(
            Topics::SYNC_INTEGRATION,
            new Message(
                [
                    'integration_id' => 'testChannel',
                    'connector_parameters' =>
                        [
                            AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => 1,
                            'parallel-process' => true
                        ],
                    'connector' => null,
                    'transport_batch_size' => 100

                ],
                MessagePriority::VERY_LOW
            )
        );
    }

    public function testProcessSetMessageQueue()
    {
        $integration = $this->getIntegration();
        $this->assertReloadEntityCall($integration);

        $this->processor->process(
            $integration,
            null,
            [
                AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => 1
            ]
        );

        self::assertMessageSent(
            Topics::SYNC_INTEGRATION,
            new Message(
                [
                    'integration_id' => 'testChannel',
                    'connector_parameters' =>
                        [
                            AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => 1,
                            'parallel-process' => true
                        ],
                    'connector' => null,
                    'transport_batch_size' => 100

                ],
                MessagePriority::VERY_LOW
            )
        );
    }

    public function testProcessParallelJobWithoutSetNewMQ()
    {
        $integration = $this->getIntegration();
        $this->assertReloadEntityCall($integration);

        $this->processor->process($integration, null, ['parallel-process' => true]);

        self::assertMessagesCount(
            Topics::SYNC_INTEGRATION,
            0
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Channel
     */
    protected function getIntegration()
    {
        $integration = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Channel')
            ->disableOriginalConstructor()
            ->getMock();

        $integration
            ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('testChannel'));

        return $integration;
    }

    /**
     * @param object $entity
     */
    protected function assertReloadEntityCall($entity)
    {
        $class = get_class($entity);
        $id = 1;
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityClass')
            ->with($entity)
            ->will($this->returnValue($class));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityIdentifier')
            ->will($this->returnValue($id));

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with($class, $id)
            ->will($this->returnValue($entity));
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $integration
     * @param object[] $addressBooks
     */
    protected function assertAddressBookCall($integration, $addressBooks)
    {
        $this->repository->expects($this->atLeastOnce())
            ->method('getConnectedAddressBooks')
            ->with($integration)
            ->will($this->returnValue($addressBooks));
    }
}
