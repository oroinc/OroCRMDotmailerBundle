<?php

namespace Oro\Bundle\DotmailerBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
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
use Oro\Component\MessageQueue\Util\JSON;
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

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ExportManager $exportManager
     * @param QueueExportManager $queueExportManager
     * @param JobRunner $jobRunner
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface $logger
     * @param JobProcessor $jobProcessor
     */
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
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive(['integrationId' => null], $body);

        if (! $body['integrationId']) {
            $this->logger->critical('The message invalid. It must have integrationId set');

            return self::REJECT;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManagerForClass(Integration::class);

        /** @var Integration $integration */
        $integration = $em->find(Integration::class, $body['integrationId']);

        if (! $integration) {
            $this->logger->error(
                sprintf('The integration not found: %s', $body['integrationId'])
            );

            return self::REJECT;
        }
        if (! $integration->isEnabled()) {
            $this->logger->error(
                sprintf('The integration is not enabled: %s', $body['integrationId'])
            );

            return self::REJECT;
        }

        $existingJob = $this->jobProcessor->findRootJobByJobNameAndStatuses(
            'oro_dotmailer:export_contacts_status_update:'.$integration->getId(),
            [Job::STATUS_NEW, Job::STATUS_RUNNING]
        );
        if ($existingJob) {
            return self::REJECT;
        }

        $jobName = 'oro_dotmailer:export_contacts_status_update:'.$body['integrationId'];
        $ownerId = $message->getMessageId();

        $result = $this->jobRunner->runUnique($ownerId, $jobName, function () use ($body, $integration) {
            /** @var EntityManagerInterface $em */
            $em = $this->doctrineHelper->getEntityManagerForClass(Integration::class);

            $em->getConnection()->getConfiguration()->setSQLLogger(null);

            $this->setTemporaryIntegrationToken($integration);

            /**
             * If previous export was not finished we need to update export results from Dotmailer.
             * If finished we need to process export faults reports
             */
            if (!$this->exportManager->isExportFinished($integration)) {
                $this->queueExportManager->updateExportResults($integration);
            } elseif (!$this->exportManager->isExportFaultsProcessed($integration)) {
                $this->queueExportManager->processExportFaults($integration);
            }

            return true;
        });

        return $result ? self::ACK : self::REJECT;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::EXPORT_CONTACTS_STATUS_UPDATE];
    }
}
