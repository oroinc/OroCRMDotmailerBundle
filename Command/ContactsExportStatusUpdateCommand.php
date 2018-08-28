<?php

namespace Oro\Bundle\DotmailerBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Job\Job;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class ContactsExportStatusUpdateCommand extends Command implements CronCommandInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

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
        $this
            ->setName('oro:cron:dotmailer:export-status:update')
            ->setDescription('Updates status of Dotmailer\'s contacts export operations.')
        ;
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
        $jobProcessor = $this->container->get('oro_message_queue.job.processor');
        $translator = $this->container->get('translator');
        foreach ($integrations as $integration) {
            /** @var Integration $integration */

            $output->writeln(sprintf('Integration "%s"', $integration->getId()));

            // check if the integration job with `new` or `in progress` status already exists.
            // @todo: Temporary solution. should be refacored during BAP-14803.
            $jobName = 'oro_dotmailer:export_contacts_status_update:'.$integration->getId();
            $existingJob = $jobProcessor->findRootJobByJobNameAndStatuses(
                $jobName,
                [Job::STATUS_NEW, Job::STATUS_RUNNING]
            );
            if ($existingJob) {
                $output->writeln(
                    sprintf(
                        'Skip "%s" integration because such job already exists with "%s" status',
                        $integration->getName(),
                        $translator->trans($existingJob->getStatus())
                    )
                );

                continue;
            }

            $jobName = 'oro_integration:sync_integration:'.$integration->getId();
            $existingJob = $jobProcessor->findRootJobByJobNameAndStatuses(
                $jobName,
                [Job::STATUS_NEW, Job::STATUS_RUNNING]
            );
            if ($existingJob) {
                $output->writeln(
                    sprintf(
                        'Skip "%s" integration because integration job already exists with "%s" status',
                        $integration->getName(),
                        $translator->trans($existingJob->getStatus())
                    )
                );

                continue;
            }

            $this->getMessageProducer()->send(
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
        /** @var RegistryInterface $doctrine */
        $doctrine = $this->container->get('doctrine');

        return $doctrine->getRepository(Integration::class);
    }

    /**
     * @return MessageProducerInterface
     */
    private function getMessageProducer()
    {
        return $this->container->get('oro_message_queue.message_producer');
    }
}
