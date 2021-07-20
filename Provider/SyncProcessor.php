<?php

namespace Oro\Bundle\DotmailerBundle\Provider;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Provider\LoggerStrategyAwareInterface;
use Oro\Bundle\IntegrationBundle\Provider\SyncProcessorInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Psr\Log\NullLogger;

/**
 * Do not allow Dotmailer integration sync when oro_dotmailer:export_contacts_status_update is running
 */
class SyncProcessor implements SyncProcessorInterface, LoggerStrategyAwareInterface
{
    /**
     * @var JobProcessor
     */
    private $jobProcessor;

    /**
     * @var SyncProcessorInterface
     */
    private $syncProcessor;

    /**
     * @var NullLogger
     */
    private $logger;

    public function __construct(JobProcessor $jobProcessor, SyncProcessorInterface $syncProcessor)
    {
        $this->jobProcessor = $jobProcessor;
        $this->syncProcessor = $syncProcessor;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function process(Integration $integration, $connector, array $connectorParameters = [])
    {
        if ($integration->getType() !== ChannelType::TYPE) {
            throw new \InvalidArgumentException(
                sprintf('Wrong integration type, "%s" expected, "%s" given', ChannelType::TYPE, $integration->getType())
            );
        }

        $existingJob = $this->jobProcessor->findRootJobByJobNameAndStatuses(
            'oro_dotmailer:export_contacts_status_update:'.$integration->getId(),
            [Job::STATUS_NEW, Job::STATUS_RUNNING]
        );
        if ($existingJob) {
            return true;
        }

        return $this->syncProcessor->process($integration, $connector, $connectorParameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getLoggerStrategy()
    {
        if ($this->syncProcessor instanceof LoggerStrategyAwareInterface) {
            return $this->syncProcessor->getLoggerStrategy();
        }

        return $this->logger;
    }
}
