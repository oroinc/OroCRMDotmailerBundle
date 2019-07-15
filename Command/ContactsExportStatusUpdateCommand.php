<?php

namespace Oro\Bundle\DotmailerBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Import export results and reports, process not exported and rejected contacts
 */
class ContactsExportStatusUpdateCommand extends Command implements CronCommandInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:dotmailer:export-status:update';

    /** @var TranslatorInterface */
    private $translator;

    /** @var JobProcessor */
    private $jobProcessor;

    /** @var ManagerRegistry */
    private $doctrine;

    /** @var MessageProducerInterface */
    private $messageProducer;

    /**
     * @param TranslatorInterface $translator
     * @param JobProcessor $jobProcessor
     * @param ManagerRegistry $doctrine
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(
        TranslatorInterface $translator,
        JobProcessor $jobProcessor,
        ManagerRegistry $doctrine,
        MessageProducerInterface $messageProducer
    ) {
        $this->translator = $translator;
        $this->jobProcessor = $jobProcessor;
        $this->doctrine = $doctrine;
        $this->messageProducer = $messageProducer;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        $count = $this->getIntegrationRepository()->countActiveIntegrations(ChannelType::TYPE);

        return ($count > 0);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Updates status of Dotmailer\'s contacts export operations.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        $output->writeln('Send export contacts status update for integration:');

        $integrations = $this->getIntegrationRepository()->findBy(['type' => ChannelType::TYPE, 'enabled' => true]);

        foreach ($integrations as $integration) {
            /** @var Integration $integration */

            $output->writeln(sprintf('Integration "%s"', $integration->getId()));

            // check if the integration job with `new` or `in progress` status already exists.
            // Temporary solution. should be refacored during BAP-14803.
            $existingJob = $this->jobProcessor->findRootJobByJobNameAndStatuses(
                'oro_dotmailer:export_contacts_status_update:'.$integration->getId(),
                [Job::STATUS_NEW, Job::STATUS_RUNNING]
            );
            if ($existingJob) {
                $output->writeln(
                    sprintf(
                        'Skip "%s" integration because such job already exists with "%s" status',
                        $integration->getName(),
                        $this->translator->trans($existingJob->getStatus())
                    )
                );

                continue;
            }

            $existingJob = $this->jobProcessor->findRootJobByJobNameAndStatuses(
                'oro_integration:sync_integration:'.$integration->getId(),
                [Job::STATUS_NEW, Job::STATUS_RUNNING]
            );
            if ($existingJob) {
                $output->writeln(
                    sprintf(
                        'Skip "%s" integration because integration job already exists with "%s" status',
                        $integration->getName(),
                        $this->translator->trans($existingJob->getStatus())
                    )
                );

                continue;
            }

            $this->messageProducer->send(
                Topics::EXPORT_CONTACTS_STATUS_UPDATE,
                new Message(
                    ['integrationId' => $integration->getId()],
                    MessagePriority::VERY_LOW
                )
            );
        }

        $output->writeln('Completed');
    }

    /**
     * @return ChannelRepository
     */
    protected function getIntegrationRepository()
    {
        return $this->doctrine->getRepository(Integration::class);
    }
}
