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
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Topic\JobAwareTopicInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExportContactsStatusUpdateProcessorTest extends \PHPUnit\Framework\TestCase
{
    use IntegrationTokenAwareTestTrait;

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
            $this->getDoctrineHelper(),
            $this->createMock(ExportManager::class),
            $this->createMock(QueueExportManager::class),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(JobProcessor::class)
        );
    }

    public function testShouldRejectMessageIfIntegrationNotExist(): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, PHP_INT_MAX)
            ->willReturn(null);

        $doctrineHelper = $this->getDoctrineHelper($entityManager);

        $message = new Message();
        $message->setBody(['integrationId' => PHP_INT_MAX]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('The integration not found: ' . PHP_INT_MAX);

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelper,
            $this->createMock(ExportManager::class),
            $this->createMock(QueueExportManager::class),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $logger,
            $this->createMock(JobProcessor::class)
        );

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfIntegrationIsNotEnabled(): void
    {
        $integration = new Integration();
        $integration->setEnabled(false);

        $entityManager = $this->getEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
            ->willReturn($integration);

        $message = new Message();
        $message->setBody(['integrationId' => 1]);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with('The integration is not enabled: 1');

        $doctrineHelper = $this->getDoctrineHelper($entityManager);

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelper,
            $this->createMock(ExportManager::class),
            $this->createMock(QueueExportManager::class),
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $logger,
            $this->createMock(JobProcessor::class)
        );

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldDoNothingIfExportFinishedAndErrorsProcessed(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManager = $this->getEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
            ->willReturn($integration);

        $doctrineHelper = $this->getDoctrineHelper($entityManager);

        $exportManager = $this->createMock(ExportManager::class);
        $queueExportManager = $this->createMock(QueueExportManager::class);
        $exportManager->expects(self::once())
            ->method('isExportFinished')
            ->willReturn(true);
        $exportManager->expects(self::once())
            ->method('isExportFaultsProcessed')
            ->willReturn(true);
        $queueExportManager->expects(self::never())
            ->method('updateExportResults');
        $queueExportManager->expects(self::never())
            ->method('processExportFaults');

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelper,
            $exportManager,
            $queueExportManager,
            new JobRunner(),
            $this->getTokenStorageMock(),
            $this->createMock(LoggerInterface::class),
            $this->createMock(JobProcessor::class)
        );

        $message = new Message();
        $message->setBody(['integrationId' => 1]);

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    /**
     * @dataProvider statusDataProvider
     */
    public function testShouldUpdateExportResultsIfExportIsNotFinished(
        bool $processResult,
        string $expectedConsumptionStatus
    ): void {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManager = $this->getEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
            ->willReturn($integration);

        $doctrineHelper = $this->getDoctrineHelper($entityManager);

        $exportManager = $this->createMock(ExportManager::class);
        $queueExportManager = $this->createMock(QueueExportManager::class);
        $exportManager->expects(self::once())
            ->method('isExportFinished')
            ->willReturn(false);
        $exportManager->expects(self::never())
            ->method('isExportFaultsProcessed');
        $queueExportManager->expects(self::once())
            ->method('updateExportResults')
            ->with(self::identicalTo($integration))
            ->willReturn($processResult);
        $queueExportManager->expects(self::never())
            ->method('processExportFaults');

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelper,
            $exportManager,
            $queueExportManager,
            new JobRunner(),
            $this->getTokenStorageMock(),
            $this->createMock(LoggerInterface::class),
            $this->createMock(JobProcessor::class)
        );

        $message = new Message();
        $message->setBody(['integrationId' => 1]);

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals($expectedConsumptionStatus, $status);
    }

    /**
     * @dataProvider statusDataProvider
     */
    public function testShouldProcessExportFaultsIfExportFinished(
        bool $processResult,
        string $expectedConsumptionStatus
    ): void {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManager = $this->getEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
            ->willReturn($integration);

        $doctrineHelper = $this->getDoctrineHelper($entityManager);

        $exportManager = $this->createMock(ExportManager::class);
        $queueExportManager = $this->createMock(QueueExportManager::class);
        $exportManager->expects(self::once())
            ->method('isExportFinished')
            ->willReturn(true);
        $exportManager->expects(self::once())
            ->method('isExportFaultsProcessed')
            ->willReturn(false);
        $queueExportManager->expects(self::never())
            ->method('updateExportResults');
        $queueExportManager->expects(self::once())
            ->method('processExportFaults')
            ->willReturn($processResult);

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelper,
            $exportManager,
            $queueExportManager,
            new JobRunner(),
            $this->getTokenStorageMock(),
            $this->createMock(LoggerInterface::class),
            $this->createMock(JobProcessor::class)
        );

        $message = new Message();
        $message->setBody(['integrationId' => 1]);

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals($expectedConsumptionStatus, $status);
    }

    public function statusDataProvider(): array
    {
        return [
            'success' => [true, MessageProcessorInterface::ACK],
            'fail' => [false, MessageProcessorInterface::REJECT]
        ];
    }

    public function testShouldRejectMessageIfIntegrationIsInProgress(): void
    {
        $integration = new Integration();
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManager = $this->getEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
            ->willReturn($integration);

        $doctrineHelper = $this->getDoctrineHelper($entityManager);

        $exportManager = $this->createMock(ExportManager::class);
        $queueExportManager = $this->createMock(QueueExportManager::class);
        $exportManager->expects(self::never())
            ->method('isExportFinished');
        $exportManager->expects(self::never())
            ->method('isExportFaultsProcessed');
        $queueExportManager->expects(self::never())
            ->method('updateExportResults');
        $queueExportManager->expects(self::never())
            ->method('processExportFaults');

        $jobProcessor = $this->createMock(JobProcessor::class);
        $jobProcessor->expects(self::once())
            ->method('findRootJobByJobNameAndStatuses')
            ->willReturn(new Job());

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelper,
            $exportManager,
            $queueExportManager,
            new JobRunner(),
            $this->createMock(TokenStorageInterface::class),
            $this->createMock(LoggerInterface::class),
            $jobProcessor
        );

        $message = new Message();
        $message->setBody(['integrationId' => 1]);

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRunExportAsUniqueJob(): void
    {
        $integration = new Integration();
        ReflectionUtil::setId($integration, 1);
        $integration->setEnabled(true);
        $integration->setOrganization(new Organization());

        $entityManager = $this->getEntityManager();
        $entityManager->expects(self::once())
            ->method('find')
            ->with(Integration::class, 1)
            ->willReturn($integration);

        $doctrineHelper = $this->getDoctrineHelper($entityManager);

        $jobRunner = new JobRunner();

        $processor = new ExportContactsStatusUpdateProcessor(
            $doctrineHelper,
            $this->createMock(ExportManager::class),
            $this->createMock(QueueExportManager::class),
            $jobRunner,
            $this->getTokenStorageMock(),
            $this->createMock(LoggerInterface::class),
            $this->createMock(JobProcessor::class)
        );

        $message = new Message();
        $message->setBody(['integrationId' => 1]);
        $message->setMessageId('theMessageId');
        $message->setProperties([
            JobAwareTopicInterface::UNIQUE_JOB_NAME => 'oro_dotmailer:export_contacts_status_update:1'
        ]);

        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);

        $uniqueJobs = $jobRunner->getRunUniqueJobs();
        self::assertCount(1, $uniqueJobs);
        self::assertEquals('oro_dotmailer:export_contacts_status_update:1', $uniqueJobs[0]['jobName']);
        self::assertEquals('theMessageId', $uniqueJobs[0]['ownerId']);
    }

    private function getEntityManager(): EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $configuration = new Configuration();

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getConnection')
            ->willReturn($connection);

        return $entityManager;
    }

    private function getDoctrineHelper(?EntityManagerInterface $entityManager = null): DoctrineHelper
    {
        $helper = $this->createMock(DoctrineHelper::class);
        $helper->expects(self::any())
            ->method('getEntityManagerForClass')
            ->willReturn($entityManager);

        return $helper;
    }
}
