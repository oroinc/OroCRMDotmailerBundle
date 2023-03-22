<?php

namespace Oro\Bundle\DotmailerBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DotmailerBundle\Async\Topic\ExportContactsStatusUpdateTopic;
use Oro\Bundle\DotmailerBundle\Model\ExportManager;
use Oro\Bundle\DotmailerBundle\Model\QueueExportManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Authentication\Token\IntegrationTokenAwareTrait;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Processor to load export statuses from Dotmailer
 */
class ExportContactsStatusUpdateProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    use IntegrationTokenAwareTrait;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ExportManager
     */
    private $exportManager;

    /**
     * @var QueueExportManager
     */
    private $queueExportManager;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JobProcessor
     */
    private $jobProcessor;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ExportManager $exportManager,
        QueueExportManager $queueExportManager,
        JobRunner $jobRunner,
        TokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        JobProcessor $jobProcessor
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->exportManager = $exportManager;
        $this->queueExportManager = $queueExportManager;
        $this->jobRunner = $jobRunner;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->jobProcessor = $jobProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = $message->getBody();

        $integration = $this->getIntegration($messageBody);
        if (!$integration) {
            return self::REJECT;
        }

        $topic = new ExportContactsStatusUpdateTopic();
        $existingJob = $this->jobProcessor->findRootJobByJobNameAndStatuses(
            $topic->createJobName($messageBody),
            [Job::STATUS_RUNNING]
        );

        if ($existingJob) {
            return self::REJECT;
        }

        $result = $this->jobRunner->runUniqueByMessage($message, function () use ($integration) {
            /** @var EntityManagerInterface $em */
            $em = $this->doctrineHelper->getEntityManagerForClass(Integration::class);

            $em->getConnection()->getConfiguration()->setSQLLogger(null);

            $this->setTemporaryIntegrationToken($integration);

            /**
             * If previous export was not finished we need to update export results from Dotmailer.
             * If finished we need to process export faults reports
             */
            if (!$this->exportManager->isExportFinished($integration)) {
                return $this->queueExportManager->updateExportResults($integration);
            }

            if (!$this->exportManager->isExportFaultsProcessed($integration)) {
                return $this->queueExportManager->processExportFaults($integration);
            }

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * @param array $body
     * @return Integration|null
     */
    private function getIntegration(array $body)
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManagerForClass(Integration::class);

        /** @var Integration $integration */
        $integration = $em->find(Integration::class, $body['integrationId']);

        if (!$integration) {
            $this->logger->error(
                sprintf('The integration not found: %s', $body['integrationId'])
            );

            return null;
        }
        if (!$integration->isEnabled()) {
            $this->logger->error(
                sprintf('The integration is not enabled: %s', $body['integrationId'])
            );

            return null;
        }

        return $integration;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [ExportContactsStatusUpdateTopic::getName()];
    }
}
