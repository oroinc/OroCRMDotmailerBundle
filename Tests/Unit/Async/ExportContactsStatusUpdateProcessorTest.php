<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DotmailerBundle\Async\ExportContactsStatusUpdateProcessor;
use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\DotmailerBundle\Model\ExportManager;
use Oro\Bundle\DotmailerBundle\Model\QueueExportManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\ClassExtensionTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExportContactsStatusUpdateProcessorTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;
    use EntityTrait;

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
            $this->createQueueExportManagerMock(),
            new JobRunner(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock(),
            $this->createJobProcessorMock()
        );
    }

    public function testShouldLogAndRejectIfMessageBodyMissIntegrationId()
    {
        $message = new Message();
        $message->setBody('[]');

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('The message invalid. It must have integrationId set');

        $processor = new ExportContactsStatusUpdateProcessor(
            $this->createDoctrineHelperStub(),
            $this->createExportManagerMock(),
            $this->createQueueExportManagerMock(),
            new JobRunner(),
            $this->createTokenStorageMock(),
            $logger,
            $this->createJobProcessorMock()
        );

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testThrowIfMessageBodyInvalidJson()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The malformed json given.');

        $processor = new ExportContactsStatusUpdateProcessor(
            $this->createDoctrineHelperStub(),
            $this->createExportManagerMock(),
            $this->createQueueExportManagerMock(),
            new JobRunner(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock(),
            $this->createJobProcessorMock()
        );

        $message = new Message();
        $message->setBody('[}');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);
    }

    public function testShouldRejectMessageIfIntegrationNotExist()
    {
        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn(null);

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $message = new Message();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('The integration not found: theIntegrationId');

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            $this->createQueueExportManagerMock(),
            new JobRunner(),
            $this->createTokenStorageMock(),
            $logger,
            $this->createJobProcessorMock()
        );

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

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
            ->willReturn($integration);

        $message = new Message();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('error')
            ->with('The integration is not enabled: theIntegrationId');

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            $this->createQueueExportManagerMock(),
            new JobRunner(),
            $this->createTokenStorageMock(),
            $logger,
            $this->createJobProcessorMock()
        );

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldDoNothingIfExportFinishedAndErrorsProcessed()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $exportManagerMock = $this->createExportManagerMock();
        $queueExportManagerMock = $this->createQueueExportManagerMock();
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFinished')
            ->willReturn(true);
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFaultsProcessed')
            ->willReturn(true);
        $queueExportManagerMock
            ->expects(self::never())
            ->method('updateExportResults');
        $queueExportManagerMock
            ->expects(self::never())
            ->method('processExportFaults');

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $exportManagerMock,
            $queueExportManagerMock,
            new JobRunner(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock(),
            $this->createJobProcessorMock()
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    /**
     * @dataProvider statusDataProvider
     * @param bool $processResult
     * @param string $expectedConsumptionStatus
     */
    public function testShouldUpdateExportResultsIfExportIsNotFinished($processResult, $expectedConsumptionStatus)
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $exportManagerMock = $this->createExportManagerMock();
        $queueExportManagerMock = $this->createQueueExportManagerMock();
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFinished')
            ->willReturn(false);
        $exportManagerMock
            ->expects(self::never())
            ->method('isExportFaultsProcessed');
        $queueExportManagerMock
            ->expects(self::once())
            ->method('updateExportResults')
            ->with(self::identicalTo($integration))
            ->willReturn($processResult);
        $queueExportManagerMock
            ->expects(self::never())
            ->method('processExportFaults');

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $exportManagerMock,
            $queueExportManagerMock,
            new JobRunner(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock(),
            $this->createJobProcessorMock()
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals($expectedConsumptionStatus, $status);
    }

    /**
     * @dataProvider statusDataProvider
     * @param bool $processResult
     * @param string $expectedConsumptionStatus
     */
    public function testShouldProcessExportFaultsIfExportFinished($processResult, $expectedConsumptionStatus)
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $exportManagerMock = $this->createExportManagerMock();
        $queueExportManagerMock = $this->createQueueExportManagerMock();
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFinished')
            ->willReturn(true);
        $exportManagerMock
            ->expects(self::once())
            ->method('isExportFaultsProcessed')
            ->willReturn(false);
        $queueExportManagerMock
            ->expects(self::never())
            ->method('updateExportResults');
        $queueExportManagerMock
            ->expects(self::once())
            ->method('processExportFaults')
            ->willReturn($processResult);

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $exportManagerMock,
            $queueExportManagerMock,
            new JobRunner(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock(),
            $this->createJobProcessorMock()
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals($expectedConsumptionStatus, $status);
    }

    public function statusDataProvider()
    {
        yield 'success' => [true, MessageProcessorInterface::ACK];
        yield 'fail' => [false, MessageProcessorInterface::REJECT];
    }

    public function testShouldRejectMessageIfIntegrationIsInProgress()
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $exportManagerMock = $this->createExportManagerMock();
        $queueExportManagerMock = $this->createQueueExportManagerMock();
        $exportManagerMock->expects(self::never())->method('isExportFinished');
        $exportManagerMock->expects(self::never())->method('isExportFaultsProcessed');
        $queueExportManagerMock->expects(self::never())->method('updateExportResults');
        $queueExportManagerMock->expects(self::never())->method('processExportFaults');

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects(self::once())
            ->method('findRootJobByJobNameAndStatuses')
            ->willReturn(new Job());

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $exportManagerMock,
            $queueExportManagerMock,
            new JobRunner(),
            $this->createTokenStorageMock(),
            $this->createLoggerMock(),
            $jobProcessor
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRunExportAsUniqueJob()
    {
        $integration = $this->getEntity(Integration::class, ['id' => 'theIntegrationId']);
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects($this->once())
            ->method('find')
            ->with(Integration::class, 'theIntegrationId')
            ->willReturn($integration);

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $jobRunner = new JobRunner();

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            $this->createQueueExportManagerMock(),
            $jobRunner,
            $this->createTokenStorageMock(),
            $this->createLoggerMock(),
            $this->createJobProcessorMock()
        );

        $message = new Message();
        $message->setBody(JSON::encode(['integrationId' => 'theIntegrationId']));
        $message->setMessageId('theMessageId');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('oro_dotmailer:export_contacts_status_update:theIntegrationId', $uniqueJobs[0]['jobName']);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface
     */
    private function createEntityManagerStub()
    {
        $configuration = new Configuration();

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connectionMock);

        return $entityManagerMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    private function createDoctrineHelperStub($entityManager = null)
    {
        $helperMock = $this->createMock(DoctrineHelper::class);
        $helperMock
            ->expects($this->any())
            ->method('getEntityManagerForClass')
            ->willReturn($entityManager);

        return $helperMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ExportManager
     */
    private function createExportManagerMock()
    {
        return $this->createMock(ExportManager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|QueueExportManager
     */
    private function createQueueExportManagerMock()
    {
        return $this->createMock(QueueExportManager::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|TokenStorageInterface
     */
    private function createTokenStorageMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|JobProcessor
     */
    private function createJobProcessorMock()
    {
        return $this->createMock(JobProcessor::class);
    }
}
