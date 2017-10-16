<?php

namespace Oro\Bundle\DotmailerBundle\Command;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\DotmailerBundle\Async\Topics;
use Oro\Bundle\DotmailerBundle\Provider\ChannelType;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Job\Job;

class ContactsClearCommand extends Command implements CronCommandInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '0 1 * * *';
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
            ->setName('oro:cron:dotmailer:contacts:clear')
            ->setDescription('Clear of Dotmailer\'s contacts operations.')
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
        $output->writeln('Send contacts clear for integration:');

        $integrations = $this->getIntegrationRepository()->findBy(['type' => ChannelType::TYPE, 'enabled' => true]);
        $jobProcessor = $this->container->get('oro_message_queue.job.processor');
        $translator = $this->container->get('translator');
        foreach ($integrations as $integration) {
            /** @var Integration $integration */
            $output->writeln(sprintf('Integration "%s"', $integration->getId()));

            $jobName = 'oro_dotmailer:contacts:clear:'.$integration->getId();
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

            $this->container->get('oro_message_queue.message_producer')->send(
                Topics::DM_CONTACTS_CLEANER,
                new Message(
                    ['integration_id' => $integration->getId()],
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
}
