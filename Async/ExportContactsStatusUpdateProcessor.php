<?php
namespace Oro\Bundle\DotmailerBundle\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DotmailerBundle\Model\ExportManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;

class ExportContactsStatusUpdateProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ExportManager
     */
    private $exportManager;

    /**
     * @var JobRunner
     */
    private $jobRunner;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param ExportManager $exportManager
     * @param JobRunner $jobRunner
     * @param LoggerInterface $logger
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ExportManager $exportManager,
        JobRunner $jobRunner,
        LoggerInterface $logger
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->exportManager = $exportManager;
        $this->jobRunner = $jobRunner;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $body = JSON::decode($message->getBody());
        $body = array_replace_recursive(['integrationId' => null], $body);

        if (! $body['integrationId']) {
            $this->logger->critical('The message invalid. It must have integrationId set', ['message' => $message]);

            return self::REJECT;
        }

        /** @var EntityManagerInterface $em */
        $em = $this->doctrineHelper->getEntityManagerForClass(Channel::class);

        /** @var Channel $channel */
        $channel = $em->find(Channel::class, $body['integrationId']);

        if (! $channel) {
            $this->logger->critical(
                sprintf('The channel not found: %s', $body['integrationId']),
                ['message' => $message]
            );

            return self::REJECT;
        }
        if (! $channel->isEnabled()) {
            $this->logger->critical(
                sprintf('The channel is not enabled: %s', $body['integrationId']),
                ['message' => $message]
            );

            return self::REJECT;
        }

        $jobName = 'oro_dotmailer:export_contacts_status_update:'.$body['integrationId'];
        $ownerId = $message->getMessageId();

        $result = $this->jobRunner->runUnique($ownerId, $jobName, function () use ($body, $channel) {
            /** @var EntityManagerInterface $em */
            $em = $this->doctrineHelper->getEntityManagerForClass(Channel::class);

            $em->getConnection()->getConfiguration()->setSQLLogger(null);

            /**
             * If previous export was not finished we need to update export results from Dotmailer.
             * If finished we need to process export faults reports
             */
            if (!$this->exportManager->isExportFinished($channel)) {
                $this->exportManager->updateExportResults($channel);
            } elseif (!$this->exportManager->isExportFaultsProcessed($channel)) {
                $this->exportManager->processExportFaults($channel);
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
