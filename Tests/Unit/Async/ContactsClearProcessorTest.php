<?php
namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Async;

use Psr\Log\LoggerInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\DotmailerBundle\Async\ContactsClearProcessor;
use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\ClassExtensionTrait;

class ContactsClearProcessorTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProcessorInterface()
    {
        $this->assertClassImplements(MessageProcessorInterface::class, ContactsClearProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface()
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, ContactsClearProcessor::class);
    }

    public function testShouldSubscribeOnContactsClearTopic()
    {
        $this->assertEquals(
            [Topics::DM_CONTACTS_CLEANER],
            ContactsClearProcessor::getSubscribedTopics()
        );
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new ContactsClearProcessor(
            $this->createDoctrineStub(),
            new JobRunner(),
            $this->createLoggerMock()
        );
    }

    public function testShouldLogAndRejectIfMessageBodyMissIntegrationId()
    {
        $message = new NullMessage();
        $message->setBody('[]');

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Invalid message: integration_id is empty', ['message' => $message])
        ;

        $processor = new ContactsClearProcessor(
            $this->createDoctrineStub(),
            new JobRunner(),
            $logger
        );

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The malformed json given.
     */
    public function testThrowIfMessageBodyInvalidJson()
    {
        $processor = new ContactsClearProcessor(
            $this->createDoctrineStub(),
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody('[}');

        $processor->process($message, new NullSession());
    }

    public function testShouldRejectMessageIfIntegrationNotExist()
    {
        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn(null)
        ;

        $doctrineStub = $this->createDoctrineStub($entityManagerMock);

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Integration with id "theIntegrationId" is not found', ['message' => $message])
        ;

        $processor = new ContactsClearProcessor(
            $doctrineStub,
            new JobRunner(),
            $logger
        );

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfIntegrationIsNotEnabled()
    {
        $integration = new Integration();
        $integration->setEnabled(false);

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration)
        ;

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('Integration with id "theIntegrationId" is not enabled', ['message' => $message])
        ;

        $doctrineStub = $this->createDoctrineStub($entityManagerMock);

        $processor = new ContactsClearProcessor(
            $doctrineStub,
            new JobRunner(),
            $logger
        );

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRunBulkRemove()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration)
        ;

        $doctrineStub = $this->createDoctrineStub($entityManagerMock);
        $this->mockBulkRemove($doctrineStub);

        $processor = new ContactsClearProcessor(
            $doctrineStub,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldRunClearAsUniqueJob()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration)
        ;

        $doctrineStub = $this->createDoctrineStub($entityManagerMock);
        $this->mockBulkRemove($doctrineStub);

        $jobRunner = new JobRunner();

        $processor = new ContactsClearProcessor(
            $doctrineStub,
            $jobRunner,
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integration_id' => 'theIntegrationId']));
        $message->setMessageId('theMessageId');

        $processor->process($message, new NullSession());

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('oro_dotmailer:contacts:clear:theIntegrationId', $uniqueJobs[0]['jobName']);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    private function createEntityManagerStub()
    {
        $configuration = new Configuration();

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration)
        ;

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock)
        ;

        return $entityManagerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    private function createDoctrineStub($entityManager = null)
    {
        $managerRegistry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()->getMock();

        $managerRegistry
            ->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager)
        ;

        return $managerRegistry;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $doctrineStub
     */
    protected function mockBulkRemove($doctrineStub)
    {
        $repository = $this->getMockBuilder(
            'Oro\Bundle\DotmailerBundle\Entity\Repository\Contact'
        )
            ->setMethods(['bulkRemoveNotExportedContacts'])
            ->disableOriginalConstructor()
            ->getMock();
        $repository
            ->expects($this->once())
            ->method('bulkRemoveNotExportedContacts')
            ->willReturn(true);
        $doctrineStub
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);
    }
}
