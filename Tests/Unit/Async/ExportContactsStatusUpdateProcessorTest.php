<?php
namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DotmailerBundle\Async\ExportContactsStatusUpdateProcessor;
use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\DotmailerBundle\Model\ExportManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\LoggerInterface;

class ExportContactsStatusUpdateProcessorTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementMessageProcessorInterface()
    {
        $this->assertClassImplements(MessageProcessorInterface::class, ExportContactsStatusUpdateProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface()
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, ExportContactsStatusUpdateProcessor::class);
    }

    public function testShouldSubscribeOnExportContactsStatusUpdateTopic()
    {
        $this->assertEquals(
            [Topics::EXPORT_CONTACTS_STATUS_UPDATE],
            ExportContactsStatusUpdateProcessor::getSubscribedTopics()
        );
    }

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new ExportContactsStatusUpdateProcessor(
            $this->createDoctrineHelperStub(),
            $this->createExportManagerMock(),
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
            ->with('The message invalid. It must have integrationId set', ['message' => $message])
        ;

        $processor = new ExportContactsStatusUpdateProcessor(
            $this->createDoctrineHelperStub(),
            $this->createExportManagerMock(),
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
        $processor = new ExportContactsStatusUpdateProcessor(
            $this->createDoctrineHelperStub(),
            $this->createExportManagerMock(),
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
            ->with(Channel::class, 'theIntegrationId')
            ->willReturn(null);
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('The channel not found: theIntegrationId', ['message' => $message])
        ;

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            new JobRunner(),
            $logger
        );


        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfIntegrationIsNotEnabled()
    {
        $channel = new Channel();
        $channel->setEnabled(false);

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Channel::class, 'theIntegrationId')
            ->willReturn($channel);
        ;

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('The channel is not enabled: theIntegrationId', ['message' => $message])
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            new JobRunner(),
            $logger
        );

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldDoNothingIfExportFinishedAndErrorsProcessed()
    {
        $channel = new Channel();
        $channel->setEnabled(true);

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Channel::class, 'theIntegrationId')
            ->willReturn($channel);
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $exportManagerMock = $this->createExportManagerMock();
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFinished')
            ->willReturn(true)
        ;
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFaultsProcessed')
            ->willReturn(true)
        ;
        $exportManagerMock
            ->expects(self::never())
            ->method('updateExportResults')
        ;
        $exportManagerMock
            ->expects(self::never())
            ->method('processExportFaults')
        ;

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $exportManagerMock,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldUpdateExportResultsIfIfExportNotFinished()
    {
        $channel = new Channel();
        $channel->setEnabled(true);

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Channel::class, 'theIntegrationId')
            ->willReturn($channel);
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $exportManagerMock = $this->createExportManagerMock();
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFinished')
            ->willReturn(false)
        ;
        $exportManagerMock
            ->expects(self::never())
            ->method('isExportFaultsProcessed')
        ;
        $exportManagerMock
            ->expects(self::once())
            ->method('updateExportResults')
            ->with(self::identicalTo($channel))
        ;
        $exportManagerMock
            ->expects(self::never())
            ->method('processExportFaults')
        ;

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $exportManagerMock,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldProcessExportFaultsIfIfExportFinished()
    {
        $channel = new Channel();
        $channel->setEnabled(true);

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Channel::class, 'theIntegrationId')
            ->willReturn($channel);
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $exportManagerMock = $this->createExportManagerMock();
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFinished')
            ->willReturn(true)
        ;
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFaultsProcessed')
            ->willReturn(false)
        ;
        $exportManagerMock
            ->expects(self::never())
            ->method('updateExportResults')
        ;
        $exportManagerMock
            ->expects(self::once())
            ->method('processExportFaults')
        ;

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $exportManagerMock,
            new JobRunner(),
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldRunExportAsUniqueJob()
    {
        $channel = new Channel();
        $channel->setEnabled(true);

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Channel::class, 'theIntegrationId')
            ->willReturn($channel);
        ;

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $jobRunner = new JobRunner();

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            $jobRunner,
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));
        $message->setMessageId('theMessageId');

        $processor->process($message, new NullSession());

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('oro_dotmailer:export_contacts_status_update:theIntegrationId', $uniqueJobs[0]['jobName']);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface
     */
    private function createEntityManagerStub()
    {
        $configuration = new Configuration();

        $connectionMock = $this->getMock(Connection::class, [], [], '', false);
        $connectionMock
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration)
        ;

        $entityManagerMock = $this->getMock(EntityManagerInterface::class);
        $entityManagerMock
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock)
        ;

        return $entityManagerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    private function createDoctrineHelperStub($entityManager = null)
    {
        $helperMock = $this->getMock(DoctrineHelper::class, [], [], '', false);
        $helperMock
            ->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturn($entityManager)
        ;

        return $helperMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExportManager
     */
    private function createExportManagerMock()
    {
        return $this->getMock(ExportManager::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }
}
