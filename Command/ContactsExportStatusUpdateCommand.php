<?php

namespace OroCRM\Bundle\DotmailerBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;

use Psr\Log\LoggerInterface;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Command\AbstractSyncCronCommand;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Component\Log\OutputLogger;

use OroCRM\Bundle\DotmailerBundle\Model\ExportManager;
use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;

class ContactsExportStatusUpdateCommand extends AbstractSyncCronCommand
{
    const NAME = 'oro:cron:dotmailer:export-status:update';
    const EXPORT_MANAGER = 'orocrm_dotmailer.export_manager';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ReverseSyncProcessor
     */
    protected $reverseSyncProcessor;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/5 * * * *';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Updates status of Dotmailer\'s contacts export operations.');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if ($output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        }

        $this->registry = $this->getService('doctrine');
        $this->logger = new OutputLogger($output);
        $this->getContainer()
            ->get('oro_integration.logger.strategy')
            ->setLogger($this->logger);

        if ($this->isJobRunning(null)) {
            $this->logger->warning('Job already running. Terminating....');

            return;
        }

        /** @var ExportManager $exportManager */
        $exportManager = $this->getService(self::EXPORT_MANAGER);
        foreach ($this->getChannels() as $channel) {
            if (!$channel->isEnabled()) {
                $this->logger->info(sprintf('Integration "%s" disabled an will be skipped', $channel->getName()));

                continue;
            }

            /**
             * If previous export was not finished we need to update export results from Dotmailer.
             * If finished we need to process export faults reports
             */
            if (!$exportManager->isExportFinished($channel)) {
                $exportManager->updateExportResults($channel);
            } elseif (!$exportManager->isExportFaultsProcessed($channel)) {
                $exportManager->processExportFaults($channel);
            }
        }
    }

    /**
     * @return Channel[]
     */
    protected function getChannels()
    {
        $channels = $this->registry
            ->getRepository('OroIntegrationBundle:Channel')
            ->findBy(['type' => ChannelType::TYPE, 'enabled' => true]);
        return $channels;
    }
}
