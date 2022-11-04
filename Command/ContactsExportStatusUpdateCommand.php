<?php
declare(strict_types=1);

namespace Oro\Bundle\DotmailerBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CronBundle\Command\CronCommandActivationInterface;
use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\DotmailerBundle\Async\Topic\ExportContactsStatusUpdateTopic;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\JobProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Schedules status updates of dotdigital contact export operations.
 */
class ContactsExportStatusUpdateCommand extends Command implements
    CronCommandScheduleDefinitionInterface,
    CronCommandActivationInterface
{
    /** @var string */
    protected static $defaultName = 'oro:cron:dotmailer:export-status:update';

    private TranslatorInterface $translator;
    private JobProcessor $jobProcessor;
    private ManagerRegistry $doctrine;
    private MessageProducerInterface $messageProducer;

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
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '*/5 * * * *';
    }

    /**
     * {@inheritDoc}
     */
    public function isActive(): bool
    {
        $count = $this->getIntegrationRepository()->countActiveIntegrations(ChannelType::TYPE);

        return ($count > 0);
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this->setDescription('Schedules status updates of dotdigital contact export operations.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command schedules status updates of dotdigital contact export operations.

This command only schedules the jobs by adding messages to the message queue, so ensure
that the message consumer processes (<info>oro:message-queue:consume</info>) are running
for the actual status updates to be performed.

  <info>php %command.full_name%</info>

HELP
            )
        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
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
                        $this->translator->trans((string) $existingJob->getStatus())
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
                        $this->translator->trans((string) $existingJob->getStatus())
                    )
                );

                continue;
            }

            $this->messageProducer->send(
                ExportContactsStatusUpdateTopic::getName(),
                ['integrationId' => $integration->getId()]
            );
        }

        $output->writeln('Completed');

        return 0;
    }

    protected function getIntegrationRepository(): ChannelRepository
    {
        return $this->doctrine->getRepository(Integration::class);
    }
}
