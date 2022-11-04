<?php

namespace Oro\Bundle\DotmailerBundle\Tests\Unit\Async;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DotmailerBundle\Async\ExportContactsStatusUpdateProcessor;
use Oro\Bundle\DotmailerBundle\Async\Topic\ExportContactsStatusUpdateTopic;
use Oro\Bundle\DotmailerBundle\Model\ExportManager;
use Oro\Bundle\DotmailerBundle\Model\QueueExportManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Authentication\Token\IntegrationTokenAwareTestTrait;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
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
    use IntegrationTokenAwareTestTrait;

    public function testShouldImplementMessageProcessorInterface(): void
    {
        $this->assertClassImplements(MessageProcessorInterface::class, ExportContactsStatusUpdateProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface(): void
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, ExportContactsStatusUpdateProcessor::class);
    }

    public function testShouldSubscribeOnExportContactsStatusUpdateTopic(): void
    {
        self::assertEquals(
            [ExportContactsStatusUpdateTopic::getName()],
            ExportContactsStatusUpdateProcessor::getSubscribedTopics()
        );
    }

    public function testCouldBeConstructedWithExpectedArguments(): void
    {
        new ExportContactsStatusUpdateProcessor(
            $this->createDoctrineHelperStub(),
            $this->createExportManagerMock(),
            $this->createQueueExportManagerMock(),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $this->createLoggerMock(),
            $this->createJobProcessorMock()
        );
    }

    public function testShouldRejectMessageIfIntegrationNotExist(): void
    {
        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects(self::once())
            ->method('find')
            ->with(Integration::class, PHP_INT_MAX)
            ->willReturn(null);

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $message = new Message();
        $message->setBody(['integrationId' => PHP_INT_MAX]);

        $logger = $this->createLoggerMock();
        $logger
            ->expects(self::once())
            ->method('error')
            ->with('The integration not found: ' . PHP_INT_MAX);

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            $this->createQueueExportManagerMock(),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $logger,
            $this->createJobProcessorMock()
        );

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfIntegrationIsNotEnabled(): void
    {
        $integration = new Integration();
        $integration->setEnabled(false);

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
            ->willReturn($integration);

        $message = new Message();
        $message->setBody(['integrationId' => 1]);

        $logger = $this->createLoggerMock();
        $logger
            ->expects(self::once())
            ->method('error')
            ->with('The integration is not enabled: 1');

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            $this->createQueueExportManagerMock(),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $logger,
            $this->createJobProcessorMock()
        );

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldDoNothingIfExportFinishedAndErrorsProcessed(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
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
            $this->getTokenStorageMock(),
            $this->createLoggerMock(),
            $this->createJobProcessorMock()
        );

        $message = new Message();
        $message->setBody(['integrationId' => 1]);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    /**
     * @dataProvider statusDataProvider
     * @param bool $processResult
     * @param string $expectedConsumptionStatus
     */
    public function testShouldUpdateExportResultsIfExportIsNotFinished($processResult, $expectedConsumptionStatus): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
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
            $this->getTokenStorageMock(),
            $this->createLoggerMock(),
            $this->createJobProcessorMock()
        );

        $message = new Message();
        $message->setBody(['integrationId' => 1]);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals($expectedConsumptionStatus, $status);
    }

    /**
     * @dataProvider statusDataProvider
     * @param bool $processResult
     * @param string $expectedConsumptionStatus
     */
    public function testShouldProcessExportFaultsIfExportFinished($processResult, $expectedConsumptionStatus): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
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
            $this->getTokenStorageMock(),
            $this->createLoggerMock(),
            $this->createJobProcessorMock()
        );

        $message = new Message();
        $message->setBody(['integrationId' => 1]);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals($expectedConsumptionStatus, $status);
    }

    public function statusDataProvider()
    {
        yield 'success' => [true, MessageProcessorInterface::ACK];
        yield 'fail' => [false, MessageProcessorInterface::REJECT];
    }

    public function testShouldRejectMessageIfIntegrationIsInProgress(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
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
            $this->createMock(TokenStorageInterface::class),
            $this->createLoggerMock(),
            $jobProcessor
        );

        $message = new Message();
        $message->setBody(['integrationId' => 1]);

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRunExportAsUniqueJob(): void
    {
        $integration = $this->getEntity(Integration::class, ['id' => 1]);
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManagerMock = $this->createEntityManagerStub();
        $entityManagerMock
            ->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
            ->willReturn($integration);

        $doctrineHelperStub = $this->createDoctrineHelperStub($entityManagerMock);

        $jobRunner = new JobRunner();

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelperStub,
            $this->createExportManagerMock(),
            $this->createQueueExportManagerMock(),
            $jobRunner,
            $this->getTokenStorageMock(),
            $this->createLoggerMock(),
            $this->createJobProcessorMock()
        );

        $message = new Message();
        $message->setBody(['integrationId' => 1]);
        $message->setMessageId('theMessageId');

        /** @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session */
        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('oro_dotmailer:export_contacts_status_update:1', $uniqueJobs[0]['jobName']);
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
            ->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock
            ->expects(self::any())
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
            ->expects(self::any())
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
