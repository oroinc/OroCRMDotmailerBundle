<?php

namespace OroCRM\Bundle\DotmailerBundle\Command;

use Doctrine\Common\Persistence\ManagerRegistry;

use Psr\Log\LoggerInterface;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\IntegrationBundle\Command\SyncCommand;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Command\ReverseSyncCommand;
use Oro\Bundle\IntegrationBundle\Command\AbstractSyncCronCommand;
use Oro\Bundle\IntegrationBundle\Provider\ReverseSyncProcessor;
use Oro\Component\Log\OutputLogger;

use OroCRM\Bundle\DotmailerBundle\Exception\RuntimeException;
use OroCRM\Bundle\DotmailerBundle\Entity\AddressBook;
use OroCRM\Bundle\DotmailerBundle\ImportExport\Reader\AbstractExportReader;
use OroCRM\Bundle\DotmailerBundle\Model\ExportManager;
use OroCRM\Bundle\DotmailerBundle\Provider\ChannelType;
use OroCRM\Bundle\DotmailerBundle\Provider\Connector\ContactConnector;

class ContactsExportCommand extends AbstractSyncCronCommand
{
    const NAME = 'oro:cron:dotmailer:export';
    const EXPORT_MANAGER = 'orocrm_dotmailer.export_manager';
    const ADDRESS_BOOK_OPTION = 'address-book';

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
            ->setDescription('Export contacts to Dotmailer')
            ->addOption(
                self::ADDRESS_BOOK_OPTION,
                null,
                InputOption::VALUE_OPTIONAL,
                'Dotmailer Address Book to sync'
            );
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

        $addressBookId = $input->getOption(self::ADDRESS_BOOK_OPTION);
        /** @var AddressBook $addressBook */
        $addressBook = null;
        if ($addressBookId) {
            $addressBook = $this->registry
                ->getManager()
                ->find('OroCRMDotmailerBundle:AddressBook', $addressBookId);
            if (!$addressBook->getChannel()) {
                throw new RuntimeException('Channel not found');
            }
        }

        $this->runExport($exportManager, $addressBook);
    }

    /**
     * @param ExportManager    $exportManager
     * @param AddressBook|null $addressBook
     */
    protected function runExport(ExportManager $exportManager, AddressBook $addressBook = null)
    {
        if ($addressBook) {
            $channels = [ $addressBook->getChannel() ];
        } else {
            $channels = $this->getChannels();
        }

        $isImportRunning = $this->isImportJobRunning();

        foreach ($channels as $channel) {
            if (!$channel->isEnabled()) {
                $this->logger->info(sprintf('Integration "%s" disabled an will be skipped', $channel->getName()));

                continue;
            }

            /**
             * If previous export not finished we need to update export results from Dotmailer
             * If after update export results all export batches is complete, import will be started,
             * because we need to update exported contacts contacts Dotmailer ID.
             * Else we need to start new export
             */
            if (!$exportManager->isExportFinished($channel)) {
                $this->logger->info(
                    sprintf(
                        'Previous export do not complete for Integration "%s", checking previous export state ...',
                        $channel->getName()
                    )
                );
                $exportManager->updateExportResults($channel);
            } elseif (!$isImportRunning) {
                $this->removePreviousAddressBookContactsExport($channel);
                $this->getReverseSyncProcessor()
                    ->process(
                        $channel,
                        ContactConnector::TYPE,
                        [
                            AbstractExportReader::ADDRESS_BOOK_RESTRICTION_OPTION => $addressBook
                        ]
                    );
                $exportManager->updateExportResults($channel);
            } else {
                $this->logger->warning(
                    sprintf(
                        'Export of Integration Channel %s not started because import is already running.',
                        $channel->getId()
                    )
                );
            }
        }
    }

    /**
     * @return bool
     */
    protected function isImportJobRunning()
    {
        $running = $this->registry->getRepository('OroIntegrationBundle:Channel')
            ->getRunningSyncJobsCount(SyncCommand::COMMAND_NAME);

        return $running > 0;
    }

    /**
     * @return ReverseSyncProcessor
     */
    protected function getReverseSyncProcessor()
    {
        if (!$this->reverseSyncProcessor) {
            $this->reverseSyncProcessor = $this->getContainer()->get(ReverseSyncCommand::SYNC_PROCESSOR);
        }

        return $this->reverseSyncProcessor;
    }

    /**
     * @param Channel $channel
     */
    protected function removePreviousAddressBookContactsExport(Channel $channel)
    {
        $this->registry
            ->getRepository('OroCRMDotmailerBundle:AddressBookContactsExport')
            ->createQueryBuilder('abContactsExport')
            ->delete()
            ->where('abContactsExport.channel =:channel')
            ->getQuery()
            ->execute(['channel' => $channel]);
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
